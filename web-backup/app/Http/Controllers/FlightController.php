<?php

namespace App\Http\Controllers;

use App\Models\Flight;
use App\Models\FlightWatch;
use App\Services\PlayApiClient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * FlightController — Handles flight board display, search, and tracking.
 * 
 * For the live board, this controller renders the initial page via Blade,
 * then Scala.js takes over with WebSocket updates for real-time data.
 * API methods serve the Scala.js components directly.
 */
class FlightController extends Controller
{
    public function __construct(
        private readonly PlayApiClient $playApi
    ) {}

    /**
     * Display the live departure/arrival board for an airport.
     * Scala.js mounts onto #flight-board-root for real-time updates.
     */
    public function board(Request $request, ?string $airport = null)
    {
        $airportIata = strtoupper($airport ?? $request->get('airport', 'JFK'));
        $boardType = $request->get('type', 'departures'); // departures | arrivals

        // Fetch initial flight data from Play API for server-side render
        $flights = $this->playApi->getFlightBoard($airportIata, $boardType);
        $airportInfo = $this->playApi->getAirportInfo($airportIata);

        return view('flights.board', [
            'flights'     => $flights,
            'airport'     => $airportInfo,
            'airportIata' => $airportIata,
            'boardType'   => $boardType,
            'wsEndpoint'  => config('wimf.websocket_url') . "/ws/board/{$airportIata}",
        ]);
    }

    /**
     * Display detailed flight information page.
     * Includes prediction data, gate history, and community contributions.
     */
    public function show(string $flightNumber, ?string $date = null)
    {
        $date = $date ?? now()->format('Y-m-d');
        
        $flight = Flight::where('flight_number', strtoupper($flightNumber))
            ->where('flight_date', $date)
            ->with(['departureAirport', 'arrivalAirport', 'airline'])
            ->firstOrFail();

        // Fetch prediction from Play API (Spark results)
        $prediction = $this->playApi->getFlightPrediction($flight->id);

        // Fetch community gate data
        $gateContributions = $flight->gateContributions()
            ->where('is_live', true)
            ->with('user')
            ->orderBy('confidence_score', 'desc')
            ->get();

        // Check if current user is watching this flight
        $isWatching = auth()->check()
            ? FlightWatch::where('user_id', auth()->id())
                ->where('flight_id', $flight->id)
                ->exists()
            : false;

        return view('flights.detail', [
            'flight'            => $flight,
            'prediction'        => $prediction,
            'gateContributions' => $gateContributions,
            'isWatching'        => $isWatching,
            'wsEndpoint'        => config('wimf.websocket_url') . "/ws/flight/{$flight->id}",
        ]);
    }

    /**
     * Search flights by number, route, or airport.
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        
        if (empty($query)) {
            return view('flights.search', ['results' => [], 'query' => '']);
        }

        $results = Flight::where('flight_number', 'ILIKE', "%{$query}%")
            ->where('flight_date', '>=', now()->subDay())
            ->with(['departureAirport', 'arrivalAirport', 'airline'])
            ->orderBy('scheduled_departure')
            ->limit(50)
            ->get();

        return view('flights.search', [
            'results' => $results,
            'query'   => $query,
        ]);
    }

    /**
     * Watch a flight — receive alerts for gate changes, delays, status updates.
     */
    public function watch(Request $request, Flight $flight)
    {
        FlightWatch::updateOrCreate(
            ['user_id' => auth()->id(), 'flight_id' => $flight->id],
            [
                'notify_gate_change' => $request->boolean('notify_gate', true),
                'notify_delay'       => $request->boolean('notify_delay', true),
                'notify_status'      => $request->boolean('notify_status', true),
            ]
        );

        return back()->with('success', "Now tracking {$flight->flight_number}");
    }

    /**
     * Stop watching a flight.
     */
    public function unwatch(Flight $flight)
    {
        FlightWatch::where('user_id', auth()->id())
            ->where('flight_id', $flight->id)
            ->delete();

        return back()->with('success', "Stopped tracking {$flight->flight_number}");
    }

    /**
     * Display user's tracked flights.
     */
    public function myFlights()
    {
        $watches = FlightWatch::where('user_id', auth()->id())
            ->with(['flight.departureAirport', 'flight.arrivalAirport', 'flight.airline'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('flights.mine', ['watches' => $watches]);
    }

    // ─────────────────────────────────────────
    // API Methods (consumed by Scala.js)
    // ─────────────────────────────────────────

    /**
     * API: Get flight board data as JSON for Scala.js component.
     */
    public function apiBoard(string $airportIata): JsonResponse
    {
        $flights = $this->playApi->getFlightBoard(strtoupper($airportIata));

        return response()->json([
            'airport' => strtoupper($airportIata),
            'flights' => $flights,
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * API: Search flights.
     */
    public function apiSearch(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        
        $results = Flight::where('flight_number', 'ILIKE', "%{$query}%")
            ->where('flight_date', '>=', now()->subDay())
            ->with(['departureAirport', 'arrivalAirport', 'airline'])
            ->orderBy('scheduled_departure')
            ->limit(20)
            ->get();

        return response()->json(['results' => $results]);
    }

    /**
     * API: Get single flight status.
     */
    public function apiStatus(string $flightId): JsonResponse
    {
        $flight = Flight::with(['departureAirport', 'arrivalAirport', 'airline'])
            ->findOrFail($flightId);

        return response()->json($flight);
    }

    /**
     * API: Get delay prediction for a flight.
     */
    public function apiPrediction(string $flightId): JsonResponse
    {
        $prediction = $this->playApi->getFlightPrediction($flightId);

        return response()->json($prediction);
    }

    /**
     * API: Get user's watched flights.
     */
    public function apiWatches(): JsonResponse
    {
        $watches = FlightWatch::where('user_id', auth()->id())
            ->with(['flight.departureAirport', 'flight.arrivalAirport'])
            ->get();

        return response()->json($watches);
    }

    /**
     * API: Add a watch.
     */
    public function apiAddWatch(string $flightId): JsonResponse
    {
        $watch = FlightWatch::updateOrCreate(
            ['user_id' => auth()->id(), 'flight_id' => $flightId],
            ['notify_gate_change' => true, 'notify_delay' => true, 'notify_status' => true]
        );

        return response()->json($watch, 201);
    }

    /**
     * API: Remove a watch.
     */
    public function apiRemoveWatch(string $flightId): JsonResponse
    {
        FlightWatch::where('user_id', auth()->id())
            ->where('flight_id', $flightId)
            ->delete();

        return response()->json(['removed' => true]);
    }
}
