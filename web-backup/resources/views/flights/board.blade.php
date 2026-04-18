@extends('layouts.app')

@section('title', 'Live Flight Board — ' . $airportIata)

@section('content')
<div class="space-y-6">

    {{-- ═══════════════ HEADER ═══════════════ --}}
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-3 mb-2">
                <h1 class="text-3xl font-bold text-white">{{ $airport['name'] ?? $airportIata }}</h1>
                <span class="px-3 py-1 rounded-full bg-wimf-600/20 text-wimf-400 text-sm font-mono font-bold">
                    {{ $airportIata }}
                </span>
            </div>
            <p class="text-gray-400 text-sm">
                Live {{ $boardType === 'departures' ? 'Departures' : 'Arrivals' }} Board
                — Updated in real-time via WebSocket
            </p>
        </div>
        
        {{-- Board Type Toggle --}}
        <div class="flex rounded-xl bg-gray-900 border border-gray-800 p-1">
            <a href="{{ route('flights.board', ['airport' => $airportIata, 'type' => 'departures']) }}"
               class="px-5 py-2 rounded-lg text-sm font-medium transition-all {{ $boardType === 'departures' ? 'bg-wimf-600 text-white shadow-lg shadow-wimf-600/20' : 'text-gray-400 hover:text-white' }}">
                Departures
            </a>
            <a href="{{ route('flights.board', ['airport' => $airportIata, 'type' => 'arrivals']) }}"
               class="px-5 py-2 rounded-lg text-sm font-medium transition-all {{ $boardType === 'arrivals' ? 'bg-wimf-600 text-white shadow-lg shadow-wimf-600/20' : 'text-gray-400 hover:text-white' }}">
                Arrivals
            </a>
        </div>
    </div>

    {{-- ═══════════════ AIRPORT SELECTOR ═══════════════ --}}
    <div class="flex items-center space-x-3">
        <form action="{{ route('flights.board') }}" method="GET" class="flex items-center space-x-2">
            <input type="text" name="airport" value="{{ $airportIata }}" 
                   maxlength="3" placeholder="IATA"
                   class="w-20 px-3 py-2 rounded-lg bg-gray-900 border border-gray-700 text-white font-mono text-center uppercase focus:border-wimf-500 focus:ring-1 focus:ring-wimf-500 outline-none transition-all">
            <button type="submit" 
                    class="px-4 py-2 rounded-lg bg-gray-800 text-gray-300 hover:bg-gray-700 hover:text-white text-sm font-medium transition-all">
                Switch Airport
            </button>
        </form>
        
        {{-- Quick airport chips --}}
        <div class="hidden lg:flex items-center space-x-2 ml-4">
            @foreach(['JFK', 'LAX', 'LHR', 'DXB', 'DEL', 'SIN'] as $iata)
                <a href="{{ route('flights.board', ['airport' => $iata]) }}"
                   class="px-3 py-1 rounded-full text-xs font-medium transition-all {{ $airportIata === $iata ? 'bg-wimf-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700 hover:text-white' }}">
                    {{ $iata }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- ═══════════════ LIVE FLIGHT BOARD ═══════════════ --}}
    {{-- This div is the mount point for the Scala.js FlightBoard component --}}
    {{-- Server renders initial data; Scala.js takes over for real-time updates --}}
    <div id="flight-board-root" 
         data-airport="{{ $airportIata }}" 
         data-board-type="{{ $boardType }}"
         data-ws-endpoint="{{ $wsEndpoint }}">
        
        {{-- Server-rendered initial board (visible before Scala.js hydrates) --}}
        <div class="rounded-2xl bg-gray-900/50 border border-gray-800/50 overflow-hidden shadow-2xl">
            
            {{-- Board Header --}}
            <div class="grid grid-cols-12 gap-4 px-6 py-3 bg-gray-900 border-b border-gray-800 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                <div class="col-span-2">Flight</div>
                <div class="col-span-3">{{ $boardType === 'departures' ? 'Destination' : 'Origin' }}</div>
                <div class="col-span-2">Scheduled</div>
                <div class="col-span-1">Gate</div>
                <div class="col-span-2">Status</div>
                <div class="col-span-2">Prediction</div>
            </div>

            {{-- Flight Rows --}}
            @forelse($flights as $index => $flight)
                <div class="grid grid-cols-12 gap-4 px-6 py-4 border-b border-gray-800/30 hover:bg-gray-800/30 transition-colors animate-board-row"
                     style="animation-delay: {{ $index * 50 }}ms">
                    
                    {{-- Flight Number --}}
                    <div class="col-span-2">
                        <a href="{{ route('flights.show', ['flightNumber' => $flight['flight_number']]) }}" 
                           class="font-mono font-bold text-white hover:text-wimf-400 transition-colors">
                            {{ $flight['flight_number'] }}
                        </a>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $flight['airline_name'] ?? '' }}</p>
                    </div>

                    {{-- Destination/Origin --}}
                    <div class="col-span-3">
                        <p class="font-medium text-gray-200">{{ $flight['destination_name'] ?? $flight['arrival_iata'] ?? '—' }}</p>
                        <p class="text-xs text-gray-500 font-mono">{{ $flight['destination_iata'] ?? '' }}</p>
                    </div>

                    {{-- Scheduled Time --}}
                    <div class="col-span-2">
                        <p class="font-mono text-gray-200">{{ $flight['scheduled_time'] ?? '—' }}</p>
                        @if(isset($flight['estimated_time']) && $flight['estimated_time'] !== $flight['scheduled_time'])
                            <p class="text-xs text-delayed font-mono">Est: {{ $flight['estimated_time'] }}</p>
                        @endif
                    </div>

                    {{-- Gate --}}
                    <div class="col-span-1">
                        @if(isset($flight['gate']))
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-gray-800 font-mono font-bold text-sm
                                {{ isset($flight['gate_source']) && $flight['gate_source'] === 'community' ? 'text-wimf-400 border border-wimf-600/30' : 'text-white' }}">
                                {{ $flight['gate'] }}
                            </span>
                            @if(isset($flight['gate_source']) && $flight['gate_source'] === 'community')
                                <p class="text-xs text-wimf-500 mt-0.5">👥 crowd</p>
                            @endif
                        @else
                            <span class="text-gray-600 text-sm">TBA</span>
                        @endif
                    </div>

                    {{-- Status --}}
                    <div class="col-span-2">
                        @php
                            $statusColors = [
                                'scheduled'  => 'text-gray-400 bg-gray-800',
                                'boarding'   => 'text-boarding bg-boarding/10 border border-boarding/20',
                                'departed'   => 'text-ontime bg-ontime/10',
                                'in_air'     => 'text-wimf-400 bg-wimf-600/10',
                                'landed'     => 'text-landed bg-landed/10',
                                'arrived'    => 'text-ontime bg-ontime/10',
                                'delayed'    => 'text-delayed bg-delayed/10 border border-delayed/20',
                                'cancelled'  => 'text-cancelled bg-cancelled/10 border border-cancelled/20',
                            ];
                            $status = $flight['status'] ?? 'scheduled';
                            $colorClass = $statusColors[$status] ?? $statusColors['scheduled'];
                        @endphp
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $colorClass }}">
                            @if($status === 'boarding')
                                <span class="w-1.5 h-1.5 rounded-full bg-boarding animate-pulse mr-1.5"></span>
                            @endif
                            {{ ucfirst(str_replace('_', ' ', $status)) }}
                        </span>
                        @if(isset($flight['delay_minutes']) && $flight['delay_minutes'] > 0)
                            <p class="text-xs text-delayed mt-1">+{{ $flight['delay_minutes'] }}min</p>
                        @endif
                    </div>

                    {{-- Prediction Badge --}}
                    <div class="col-span-2">
                        {{-- Scala.js PredictionCard mounts per-row here --}}
                        <div class="prediction-mount" data-flight-id="{{ $flight['id'] ?? '' }}">
                            @if(isset($flight['delay_probability']))
                                @php $prob = $flight['delay_probability'] * 100; @endphp
                                <div class="flex items-center space-x-2">
                                    <div class="w-12 h-1.5 rounded-full bg-gray-800 overflow-hidden">
                                        <div class="h-full rounded-full {{ $prob > 60 ? 'bg-cancelled' : ($prob > 30 ? 'bg-delayed' : 'bg-ontime') }}"
                                             style="width: {{ $prob }}%"></div>
                                    </div>
                                    <span class="text-xs font-mono {{ $prob > 60 ? 'text-cancelled' : ($prob > 30 ? 'text-delayed' : 'text-ontime') }}">
                                        {{ round($prob) }}%
                                    </span>
                                </div>
                                @if(isset($flight['primary_cause']) && $flight['primary_cause'] !== 'none')
                                    <p class="text-xs text-gray-500 mt-0.5 capitalize">{{ $flight['primary_cause'] }}</p>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-6 py-16 text-center">
                    <p class="text-gray-500 text-lg">No flights currently showing for {{ $airportIata }}</p>
                    <p class="text-gray-600 text-sm mt-2">Try a different airport or check back later</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- ═══════════════ BOARD FOOTER ═══════════════ --}}
    <div class="flex items-center justify-between text-xs text-gray-600">
        <div class="flex items-center space-x-2">
            <span class="w-2 h-2 rounded-full bg-ontime animate-pulse"></span>
            <span>Live — Updates via WebSocket</span>
        </div>
        <div class="flex items-center space-x-4">
            <span>👥 = Community-sourced gate</span>
            <span>Bar = Delay probability from Spark ML</span>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Configure the Scala.js FlightBoard component on mount
    document.addEventListener('DOMContentLoaded', function() {
        if (window.WIMFFrontend && window.WIMFFrontend.FlightBoard) {
            window.WIMFFrontend.FlightBoard.mount(
                document.getElementById('flight-board-root'),
                {
                    airport: '{{ $airportIata }}',
                    boardType: '{{ $boardType }}',
                    wsEndpoint: '{{ $wsEndpoint }}'
                }
            );
        }
    });
</script>
@endpush
