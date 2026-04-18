@extends('layouts.app')
@section('title', 'Where Is My Flight — Home')

@section('content')
<div class="space-y-12">

    {{-- ═══════════════ HERO ═══════════════ --}}
    <div class="text-center space-y-6 pt-8">
        <h1 class="text-5xl md:text-6xl font-extrabold">
            <span class="bg-gradient-to-r from-wimf-300 via-wimf-400 to-wimf-600 bg-clip-text text-transparent">
                Where Is My Flight?
            </span>
        </h1>
        <p class="text-gray-400 text-lg max-w-2xl mx-auto leading-relaxed">
            Real-time flight tracking with <span class="text-wimf-400 font-semibold">AI delay predictions</span>
            and <span class="text-wimf-400 font-semibold">community-powered gate info</span>.
            Know your gate before the board updates.
        </p>

        {{-- Search Bar --}}
        <form action="{{ route('flights.search') }}" method="GET" class="max-w-xl mx-auto">
            <div class="flex items-center rounded-2xl bg-gray-900 border border-gray-700 focus-within:border-wimf-500 focus-within:ring-2 focus-within:ring-wimf-500/20 transition-all overflow-hidden shadow-2xl shadow-wimf-900/10">
                <input type="text" name="q" placeholder="Search flight number, airport, or route..." 
                       class="flex-1 px-6 py-4 bg-transparent text-white placeholder-gray-500 outline-none text-lg">
                <button type="submit" class="px-8 py-4 bg-wimf-600 text-white font-semibold hover:bg-wimf-500 transition-colors">
                    Search
                </button>
            </div>
        </form>
    </div>

    {{-- ═══════════════ QUICK AIRPORT SELECT ═══════════════ --}}
    <div>
        <h2 class="text-lg font-semibold text-gray-300 mb-4">Quick — Pick an Airport</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            @foreach($popularAirports as $airport)
                <a href="{{ route('flights.board', ['airport' => $airport['iata']]) }}"
                   class="group rounded-xl bg-gray-900/50 border border-gray-800 p-4 hover:border-wimf-500/50 hover:bg-gray-800/50 transition-all hover:shadow-lg hover:shadow-wimf-600/5">
                    <p class="font-mono font-bold text-2xl text-white group-hover:text-wimf-400 transition-colors">
                        {{ $airport['iata'] }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1">{{ $airport['city'] }}</p>
                </a>
            @endforeach
        </div>
    </div>

    {{-- ═══════════════ LIVE STATS ═══════════════ --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-xl bg-gradient-to-br from-wimf-900/50 to-gray-900 border border-wimf-800/30 p-6">
            <p class="text-3xl font-bold text-white">{{ number_format($stats['active_flights']) }}</p>
            <p class="text-sm text-gray-400 mt-1">Active Flights Today</p>
        </div>
        <div class="rounded-xl bg-gradient-to-br from-boarding/10 to-gray-900 border border-boarding/20 p-6">
            <p class="text-3xl font-bold text-white">{{ number_format($stats['community_updates']) }}</p>
            <p class="text-sm text-gray-400 mt-1">Community Updates Today</p>
        </div>
        <div class="rounded-xl bg-gradient-to-br from-ontime/10 to-gray-900 border border-ontime/20 p-6">
            <p class="text-3xl font-bold text-white">{{ number_format($stats['tracked_flights']) }}</p>
            <p class="text-sm text-gray-400 mt-1">Flights Being Tracked</p>
        </div>
    </div>

    {{-- ═══════════════ DELAYED FLIGHTS ═══════════════ --}}
    @if($delayedFlights->count() > 0)
    <div>
        <h2 class="text-lg font-semibold text-delayed mb-4 flex items-center space-x-2">
            <span class="w-2 h-2 rounded-full bg-delayed animate-pulse"></span>
            <span>Currently Delayed</span>
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($delayedFlights as $flight)
                <a href="{{ route('flights.show', ['flightNumber' => $flight->flight_number]) }}"
                   class="rounded-xl bg-gray-900/50 border border-delayed/20 p-4 hover:border-delayed/40 transition-all group">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-mono font-bold text-white group-hover:text-delayed transition-colors">
                            {{ $flight->flight_number }}
                        </span>
                        <span class="text-delayed text-sm font-semibold">+{{ $flight->delay_minutes }}min</span>
                    </div>
                    <p class="text-sm text-gray-400">
                        {{ $flight->departureAirport->iata_code ?? '?' }} → {{ $flight->arrivalAirport->iata_code ?? '?' }}
                    </p>
                    @if($flight->delay_reason)
                        <p class="text-xs text-gray-500 mt-1 capitalize">{{ $flight->delay_reason }}</p>
                    @endif
                </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ═══════════════ RECENT COMMUNITY ACTIVITY ═══════════════ --}}
    @if($recentContributions->count() > 0)
    <div>
        <h2 class="text-lg font-semibold text-gray-300 mb-4">Recent Community Updates</h2>
        <div class="space-y-2">
            @foreach($recentContributions as $contrib)
                <div class="flex items-center space-x-4 rounded-lg bg-gray-900/30 border border-gray-800/30 px-4 py-3">
                    <div class="w-8 h-8 rounded-full bg-wimf-600/20 flex items-center justify-center text-xs font-bold text-wimf-400">
                        {{ strtoupper(substr($contrib->user->display_name ?? '?', 0, 1)) }}
                    </div>
                    <div class="flex-1 text-sm">
                        <span class="text-gray-300">{{ $contrib->user->display_name ?? 'User' }}</span>
                        <span class="text-gray-500"> updated gate to </span>
                        <span class="font-mono font-bold text-wimf-400">{{ $contrib->gate_number }}</span>
                        <span class="text-gray-500"> for </span>
                        <span class="font-mono text-white">{{ $contrib->flight->flight_number ?? 'Unknown' }}</span>
                    </div>
                    <span class="text-xs text-gray-600">{{ $contrib->created_at->diffForHumans() }}</span>
                </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection
