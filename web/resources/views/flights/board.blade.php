@extends('layouts.app')

@section('title', 'Live Flight Board — ' . $airportIata)

@section('content')
<div class="space-y-6 fade-in-up">

    {{-- ═══════════════ HEADER ═══════════════ --}}
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-3 mb-2">
                <h1 class="text-3xl font-bold" style="color: var(--text-primary)">{{ $airport['name'] ?? $airportIata }}</h1>
                <span class="px-3 py-1 rounded-full bg-wimf-600/20 text-wimf-400 text-sm font-mono font-bold">
                    {{ $airportIata }}
                </span>
            </div>
            <p class="text-sm" style="color: var(--text-secondary)">
                <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse inline-block mr-1"></span>
                Live Aircraft Near {{ $airportIata }} — Real-time via OpenSky Network
                <span class="font-mono text-wimf-400 ml-2">{{ count($flights) }} aircraft</span>
            </p>
        </div>
    </div>

    {{-- ═══════════════ AIRPORT SELECTOR ═══════════════ --}}
    <div class="flex items-center space-x-3">
        <form action="{{ route('flights.board') }}" method="GET" class="flex items-center space-x-2">
            <input type="text" name="airport" value="{{ $airportIata }}" 
                   maxlength="3" placeholder="IATA"
                   class="w-20 px-3 py-2 rounded-lg border font-mono text-center uppercase outline-none transition-all focus:border-wimf-500 focus:ring-1 focus:ring-wimf-500"
                   style="background: var(--bg-card); border-color: var(--border-card); color: var(--text-primary)">
            <button type="submit" 
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-all hover:text-white"
                    style="background: var(--bg-card); color: var(--text-secondary)">
                Switch Airport
            </button>
        </form>
        
        {{-- Quick airport chips --}}
        <div class="hidden lg:flex items-center space-x-2 ml-4">
            @foreach(['JFK', 'LAX', 'LHR', 'DXB', 'DEL', 'SIN', 'BOM', 'BLR', 'HYD'] as $iata)
                <a href="{{ route('flights.board', ['airport' => $iata]) }}"
                   class="px-3 py-1 rounded-full text-xs font-medium transition-all {{ $airportIata === $iata ? 'bg-wimf-600 text-white' : '' }}"
                   style="{{ $airportIata !== $iata ? 'background: var(--bg-card); color: var(--text-secondary)' : '' }}">
                    {{ $iata }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- ═══════════════ FLIGHT CARDS GRID ═══════════════ --}}
    @if(count($flights) > 0)
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 stagger-children">
        @foreach($flights as $index => $flight)
            @php
                $airline = getAirlineFromCallsign($flight['callsign'] ?? 'UNKNOWN');
                $altColor = $flight['altitude'] > 10000 ? 'text-emerald-400' : ($flight['altitude'] > 5000 ? 'text-amber-400' : 'text-rose-400');
                
                $flightStatus = 'in_air';
                if ($flight['on_ground']) {
                    $flightStatus = 'landed';
                } elseif ($flight['altitude'] < 2000) {
                    $flightStatus = 'departed';
                }

                $statusColors = [
                    'in_air'   => 'bg-wimf-600/20 text-wimf-400',
                    'departed' => 'bg-emerald-500/20 text-emerald-400',
                    'landed'   => 'bg-amber-500/20 text-amber-400',
                ];
                $statusClass = $statusColors[$flightStatus] ?? 'bg-gray-500/20 text-gray-400';
                $locationDesc = ($flight['on_ground'] ? 'Parked in ' : 'Over ') . $flight['origin_country'];
            @endphp
            <a href="{{ route('flight.profile', ['callsign' => trim($flight['callsign'] ?? 'UNKNOWN')]) }}"
               class="glass-card rounded-2xl p-5 hover:scale-[1.02] transition-all duration-300 block"
               style="animation-delay: {{ ($index % 20) * 30 }}ms">
                
                {{-- Header --}}
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <div class="flex items-center space-x-2">
                            <h3 class="font-mono font-bold text-lg" style="color: var(--text-primary)">{{ $flight['callsign'] ?? 'N/A' }}</h3>
                            @if(!empty($airline['iata']))
                                <span class="px-2 py-0.5 rounded-full bg-wimf-600/20 text-wimf-400 text-xs font-bold">
                                    {{ $airline['display'] ?? '' }}
                                </span>
                            @endif
                        </div>
                        @if(!empty($airline['name']))
                            <p class="text-sm font-medium text-wimf-400">{{ $airline['name'] }}</p>
                        @endif
                        <p class="text-xs flex items-center gap-1 mt-0.5" style="color: var(--text-muted)">
                            <span>{{ getCountryFlag($flight['origin_country']) }}</span>
                            <span>{{ $flight['origin_country'] }}</span>
                        </p>
                    </div>
                    <div class="heading-indicator text-2xl" 
                         style="transform: rotate({{ $flight['heading'] }}deg)">
                        ✈
                    </div>
                </div>

                {{-- Location --}}
                @if($locationDesc)
                    <p class="text-xs mb-2 flex items-center gap-1" style="color: var(--text-secondary)">
                        <span>📍</span>
                        <span>{{ $locationDesc }}</span>
                    </p>
                @endif

                {{-- Data Grid --}}
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <p class="text-xs" style="color: var(--text-muted)">Altitude</p>
                        <p class="font-mono font-semibold {{ $altColor }}">{{ number_format($flight['altitude']) }}m</p>
                    </div>
                    <div>
                        <p class="text-xs" style="color: var(--text-muted)">Speed</p>
                        <p class="font-mono font-semibold text-wimf-400">{{ number_format(round(($flight['velocity'] ?? 0) * 3.6)) }} km/h</p>
                    </div>
                    <div>
                        <p class="text-xs" style="color: var(--text-muted)">Heading</p>
                        @php
                            $dirs = ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW'];
                            $dir = $dirs[round($flight['heading'] / 45) % 8];
                        @endphp
                        <p class="font-mono font-semibold" style="color: var(--text-secondary)">{{ $flight['heading'] }}° {{ $dir }}</p>
                    </div>
                    <div>
                        <p class="text-xs" style="color: var(--text-muted)">Status</p>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                            {{ ucfirst(str_replace('_', ' ', $flightStatus)) }}
                        </span>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="mt-3 flex items-center justify-between">
                    @if($flight['on_ground'])
                        <span class="px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-400 text-xs font-medium">On Ground</span>
                    @else
                        <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 text-xs font-medium">In Flight</span>
                    @endif
                    <span class="text-xs font-mono" style="color: var(--text-muted)">{{ $flight['icao24'] ?? '' }}</span>
                </div>
            </a>
        @endforeach
    </div>
    @else
        <div class="glass-card rounded-2xl px-6 py-16 text-center">
            <p class="text-lg" style="color: var(--text-muted)">No aircraft currently near {{ $airportIata }}</p>
            <p class="text-sm mt-2" style="color: var(--text-muted)">Try a different airport or check back later</p>
        </div>
    @endif

    {{-- ═══════════════ BOARD FOOTER ═══════════════ --}}
    <div class="flex items-center justify-between text-xs" style="color: var(--text-muted)">
        <div class="flex items-center space-x-2">
            <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
            <span>Live — Data from OpenSky Network ADS-B</span>
        </div>
        <div>
            <span>{{ count($flights) }} aircraft near {{ $airportIata }}</span>
        </div>
    </div>
</div>
@endsection
