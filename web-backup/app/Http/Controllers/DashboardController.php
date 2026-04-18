<?php

namespace App\Http\Controllers;

use App\Models\Flight;
use App\Models\GateContribution;
use App\Models\FlightWatch;
use Illuminate\Http\Request;

/**
 * DashboardController — Home page and user profile.
 */
class DashboardController extends Controller
{
    /**
     * Home page — shows quick airport selector + trending flights.
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
            ->with(['departureAirport', 'arrivalAirport', 'airline'])
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
            'tracked_flights'   => FlightWatch::whereDate('created_at', now())->count(),
        ];

        return view('dashboard', [
            'popularAirports'     => $popularAirports,
            'delayedFlights'      => $delayedFlights,
            'recentContributions' => $recentContributions,
            'stats'               => $stats,
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
            ->with(['flight.departureAirport', 'flight.arrivalAirport'])
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
