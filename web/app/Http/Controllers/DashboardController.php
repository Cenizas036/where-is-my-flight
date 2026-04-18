<?php

namespace App\Http\Controllers;

use App\Models\Flight;
use App\Models\GateContribution;
use App\Models\FlightWatch;
use App\Services\OpenSkyService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * DashboardController — Home page, user profile, and live global flight feed.
 */
class DashboardController extends Controller
{
    /**
     * Home page — public landing with CTA to sign up/login.
     */
    public function index()
    {
        // Popular airports for quick-select
        $popularAirports = [
            ['iata' => 'JFK', 'name' => 'New York JFK',      'city' => 'New York'],
            ['iata' => 'LAX', 'name' => 'Los Angeles LAX',    'city' => 'Los Angeles'],
            ['iata' => 'LHR', 'name' => 'London Heathrow',    'city' => 'London'],
            ['iata' => 'DXB', 'name' => 'Dubai International','city' => 'Dubai'],
            ['iata' => 'DEL', 'name' => 'Delhi IGI',          'city' => 'New Delhi'],
            ['iata' => 'SIN', 'name' => 'Singapore Changi',   'city' => 'Singapore'],
        ];

        // Currently delayed flights (trending)
        $delayedFlights = Flight::where('flight_date', now()->format('Y-m-d'))
            ->where('status', 'delayed')
            ->orderBy('delay_minutes', 'desc')
            ->limit(5)
            ->get();

        // Recent community contributions
        $recentContributions = GateContribution::where('is_live', true)
            ->with(['user', 'flight'])
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        // Stats
        $stats = [
            'active_flights'    => Flight::where('flight_date', now()->format('Y-m-d'))
                ->whereNotIn('status', ['arrived', 'cancelled'])->count(),
            'community_updates' => GateContribution::where('is_live', true)
                ->whereDate('created_at', now())->count(),
            'tracked_flights'   => FlightWatch::count(),
        ];

        return view('dashboard', [
            'popularAirports'     => $popularAirports,
            'delayedFlights'      => $delayedFlights,
            'recentContributions' => $recentContributions,
            'stats'               => $stats,
        ]);
    }

    /**
     * Live Feed — Real-time global aircraft from OpenSky Network.
     * Requires authentication.
     */
    public function liveFeed(Request $request)
    {
        $openSky = new OpenSkyService();
        $country = $request->get('country', 'all');
        
        $flights = $openSky->getFlightsByCountry($country !== 'all' ? $country : null);
        $countries = $openSky->getActiveCountries();
        $stats = $openSky->getStats();

        // Limit to 200 for initial page render
        $flights = array_slice($flights, 0, 200);

        // Enrich with location description
        $flights = array_map(function ($f) {
            $f['location_desc'] = getLocationDescription($f['latitude'], $f['longitude'], $f['on_ground']);
            return $f;
        }, $flights);

        return view('flights.livefeed', [
            'flights'         => $flights,
            'countries'       => $countries,
            'stats'           => $stats,
            'selectedCountry' => $country,
        ]);
    }

    /**
     * API endpoint for AJAX refresh of live flights.
     */
    public function apiLiveFlights(Request $request): JsonResponse
    {
        $openSky = new OpenSkyService();
        $country = $request->get('country', 'all');
        
        $flights = $openSky->getFlightsByCountry($country !== 'all' ? $country : null);
        $stats = $openSky->getStats();

        // Limit for JSON response
        $flights = array_slice($flights, 0, 200);

        // Enrich with location description
        $flights = array_map(function ($f) {
            $f['location_desc'] = getLocationDescription($f['latitude'], $f['longitude'], $f['on_ground']);
            return $f;
        }, $flights);

        return response()->json([
            'flights' => $flights,
            'stats'   => $stats,
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * User profile page — shows trust score, contribution history, tracked flights.
     */
    public function profile()
    {
        $user = auth()->user()->load('trustScore');

        $recentContributions = GateContribution::where('user_id', $user->id)
            ->with('flight')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $watchedFlights = FlightWatch::where('user_id', $user->id)
            ->with('flight')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('profile', [
            'user'                => $user,
            'recentContributions' => $recentContributions,
            'watchedFlights'      => $watchedFlights,
        ]);
    }
}
