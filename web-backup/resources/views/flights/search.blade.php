@extends('layouts.app')
@section('title', $query ? "Search: {$query}" : 'Search Flights')

@section('content')
<div class="space-y-8">

    {{-- ═══════════════ SEARCH HEADER ═══════════════ --}}
    <div class="text-center space-y-4">
        <h1 class="text-3xl font-bold text-white">Search Flights</h1>
        <p class="text-gray-400">Find by flight number, airport, or route</p>
    </div>

    {{-- ═══════════════ SEARCH FORM ═══════════════ --}}
    <form action="{{ route('flights.search') }}" method="GET" class="max-w-2xl mx-auto">
        <div class="flex items-center rounded-2xl bg-gray-900 border border-gray-700 focus-within:border-wimf-500 focus-within:ring-2 focus-within:ring-wimf-500/20 transition-all overflow-hidden shadow-2xl">
            <svg class="w-5 h-5 text-gray-500 ml-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" name="q" value="{{ $query }}"
                   placeholder="e.g. AA1234, JFK, LAX→SFO"
                   class="flex-1 px-4 py-4 bg-transparent text-white placeholder-gray-500 outline-none text-lg"
                   autofocus>
            <button type="submit"
                    class="px-8 py-4 bg-wimf-600 text-white font-semibold hover:bg-wimf-500 transition-colors">
                Search
            </button>
        </div>
    </form>

    {{-- ═══════════════ RESULTS ═══════════════ --}}
    @if($query)
        <div>
            <p class="text-sm text-gray-500 mb-4">
                {{ count($results) }} result{{ count($results) !== 1 ? 's' : '' }} for
                <span class="text-white font-mono">{{ $query }}</span>
            </p>

            @if(count($results) > 0)
                <div class="space-y-3 stagger-children">
                    @foreach($results as $flight)
                        <a href="{{ route('flights.show', ['flightNumber' => $flight->flight_number, 'date' => $flight->flight_date]) }}"
                           class="glass-card block rounded-xl p-5 hover:border-wimf-500/30 transition-all group">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-6">
                                    {{-- Flight Number --}}
                                    <div>
                                        <p class="font-mono font-bold text-xl text-white group-hover:text-wimf-400 transition-colors">
                                            {{ $flight->flight_number }}
                                        </p>
                                        <p class="text-xs text-gray-500">{{ $flight->airline->name ?? '' }}</p>
                                    </div>

                                    {{-- Route --}}
                                    <div class="flex items-center space-x-3">
                                        <div class="text-center">
                                            <p class="font-mono font-bold text-white">{{ $flight->departureAirport->iata_code }}</p>
                                            <p class="text-xs text-gray-600">{{ $flight->departureAirport->city ?? '' }}</p>
                                        </div>
                                        <span class="text-gray-600">→</span>
                                        <div class="text-center">
                                            <p class="font-mono font-bold text-white">{{ $flight->arrivalAirport->iata_code }}</p>
                                            <p class="text-xs text-gray-600">{{ $flight->arrivalAirport->city ?? '' }}</p>
                                        </div>
                                    </div>

                                    {{-- Time --}}
                                    <div>
                                        <p class="font-mono text-gray-300">
                                            {{ \Carbon\Carbon::parse($flight->scheduled_departure)->format('H:i') }}
                                        </p>
                                        <p class="text-xs text-gray-600">{{ $flight->flight_date }}</p>
                                    </div>
                                </div>

                                {{-- Status --}}
                                <div class="flex items-center space-x-3">
                                    @php
                                        $statusColors = [
                                            'scheduled'  => 'text-gray-400 bg-gray-800',
                                            'boarding'   => 'text-boarding bg-boarding/10',
                                            'departed'   => 'text-ontime bg-ontime/10',
                                            'in_air'     => 'text-wimf-400 bg-wimf-600/10',
                                            'landed'     => 'text-landed bg-landed/10',
                                            'delayed'    => 'text-delayed bg-delayed/10',
                                            'cancelled'  => 'text-cancelled bg-cancelled/10',
                                        ];
                                        $sColor = $statusColors[$flight->status] ?? 'text-gray-400 bg-gray-800';
                                    @endphp
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $sColor }}">
                                        {{ ucfirst(str_replace('_', ' ', $flight->status)) }}
                                    </span>

                                    @if($flight->delay_minutes > 0)
                                        <span class="text-delayed text-sm font-mono">+{{ $flight->delay_minutes }}min</span>
                                    @endif

                                    @if($flight->departure_gate)
                                        <span class="gate-badge text-xs">{{ $flight->departure_gate }}</span>
                                    @endif

                                    <svg class="w-4 h-4 text-gray-600 group-hover:text-wimf-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="text-center py-16">
                    <p class="text-gray-500 text-lg">No flights found for "{{ $query }}"</p>
                    <p class="text-gray-600 text-sm mt-2">Try a flight number (e.g. AA1234) or airport code (e.g. JFK)</p>
                </div>
            @endif
        </div>
    @else
        {{-- Empty state --}}
        <div class="text-center py-16">
            <svg class="w-16 h-16 text-gray-700 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <p class="text-gray-500">Enter a flight number, airport, or route to search</p>
        </div>
    @endif
</div>
@endsection
