@extends('layouts.app')
@section('title', 'My Tracked Flights')

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">My Flights</h1>
            <p class="text-gray-400 text-sm mt-1">Flights you're tracking for updates</p>
        </div>
        <a href="{{ route('flights.search') }}"
           class="px-4 py-2 rounded-xl bg-wimf-600 text-white text-sm font-semibold hover:bg-wimf-500 transition-all shadow-lg shadow-wimf-600/20">
            + Track a Flight
        </a>
    </div>

    @if($watches->count() > 0)
        <div class="space-y-3 stagger-children">
            @foreach($watches as $watch)
                @php $flight = $watch->flight; @endphp
                <div class="glass-card rounded-xl p-5">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-6">
                            {{-- Flight --}}
                            <a href="{{ route('flight.profile', ['callsign' => $flight->flight_number]) }}"
                               class="font-mono font-bold text-xl text-white hover:text-wimf-400 transition-colors">
                                {{ $flight->flight_number }}
                            </a>

                            {{-- Route --}}
                            <div class="flex items-center space-x-2 text-gray-300">
                                <span class="font-mono">{{ $flight->departureAirport->iata_code ?? '?' }}</span>
                                <span class="text-gray-600">→</span>
                                <span class="font-mono">{{ $flight->arrivalAirport->iata_code ?? '?' }}</span>
                            </div>

                            {{-- Time --}}
                            <span class="text-sm text-gray-400 font-mono">
                                {{ \Carbon\Carbon::parse($flight->scheduled_departure)->format('M d, H:i') }}
                            </span>

                            {{-- Status --}}
                            @php
                                $sColors = [
                                    'scheduled' => 'text-gray-400 bg-gray-800',
                                    'boarding'  => 'text-boarding bg-boarding/10',
                                    'departed'  => 'text-ontime bg-ontime/10',
                                    'in_air'    => 'text-wimf-400 bg-wimf-600/10',
                                    'landed'    => 'text-landed bg-landed/10',
                                    'delayed'   => 'text-delayed bg-delayed/10',
                                    'cancelled' => 'text-cancelled bg-cancelled/10',
                                ];
                            @endphp
                            <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $sColors[$flight->status] ?? 'text-gray-400 bg-gray-800' }}">
                                {{ ucfirst(str_replace('_', ' ', $flight->status)) }}
                            </span>

                            @if($flight->delay_minutes > 0)
                                <span class="text-delayed text-sm font-mono">+{{ $flight->delay_minutes }}min</span>
                            @endif
                        </div>

                        <div class="flex items-center space-x-4">
                            {{-- Notification toggles --}}
                            <div class="flex items-center space-x-2 text-xs text-gray-500">
                                @if($watch->notify_gate_change) <span class="wimf-tooltip" data-tooltip="Gate alerts">🚪</span> @endif
                                @if($watch->notify_delay) <span class="wimf-tooltip" data-tooltip="Delay alerts">⏰</span> @endif
                                @if($watch->notify_status) <span class="wimf-tooltip" data-tooltip="Status alerts">📊</span> @endif
                            </div>

                            {{-- Unwatch --}}
                            <form method="POST" action="{{ route('flights.unwatch', $flight) }}">
                                @csrf @method('DELETE')
                                <button class="text-gray-600 hover:text-red-400 transition-colors text-sm">
                                    Remove
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{ $watches->links() }}
    @else
        <div class="text-center py-20">
            <svg class="w-12 h-12 text-wimf-400 mx-auto mb-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z"/>
            </svg>
            <p class="text-gray-500 text-lg">You're not tracking any flights</p>
            <p class="text-gray-600 text-sm mt-2">Search for a flight and tap "Track Flight" to get live updates</p>
            <a href="{{ route('flights.search') }}"
               class="inline-block mt-6 px-6 py-3 rounded-xl bg-wimf-600 text-white font-semibold hover:bg-wimf-500 transition-all">
                Find a Flight
            </a>
        </div>
    @endif
</div>
@endsection
