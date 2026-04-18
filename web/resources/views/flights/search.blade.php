@extends('layouts.app')
@section('title', $query ? "Search: {$query}" : 'Search Flights')

@section('content')
<div class="space-y-8 fade-in-up">

    {{-- ═══════════════ SEARCH HEADER ═══════════════ --}}
    <div class="text-center space-y-4">
        <h1 class="text-3xl font-bold gradient-text">Search Live Flights</h1>
        <p style="color: var(--text-secondary)">Find by airline, callsign, country, or airport code — powered by real-time data</p>
    </div>

    {{-- ═══════════════ SEARCH FORM ═══════════════ --}}
    <form action="{{ route('flights.search') }}" method="GET" class="max-w-2xl mx-auto">
        <div class="flex items-center rounded-2xl border focus-within:border-wimf-500 focus-within:ring-2 focus-within:ring-wimf-500/20 transition-all overflow-hidden shadow-2xl"
             style="background: var(--bg-card); border-color: var(--border-card);">
            <svg class="w-5 h-5 ml-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--text-muted)">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" name="q" value="{{ $query }}"
                   placeholder="e.g. IndiGo, Emirates, DEL, JFK, United States..."
                   class="flex-1 px-4 py-4 bg-transparent placeholder-gray-500 outline-none text-lg"
                   style="color: var(--text-primary)" autofocus>
            <button type="submit" class="px-8 py-4 bg-wimf-600 text-white font-semibold hover:bg-wimf-500 transition-colors">
                Search
            </button>
        </div>
    </form>

    {{-- ═══════════════ POPULAR AIRLINES ═══════════════ --}}
    @if(empty($query) && isset($airlines))
    <div class="fade-in-up">
        <h2 class="text-sm font-semibold mb-3" style="color: var(--text-muted)">Popular Airlines — Click to Search</h2>
        <div class="flex flex-wrap gap-2">
            @foreach($airlines as $al)
                <a href="{{ route('flights.search', ['q' => $al['name']]) }}"
                   class="glass-card px-4 py-2 rounded-xl text-sm font-medium hover:scale-105 transition-all flex items-center space-x-2">
                    <span>{{ $al['country'] }}</span>
                    <span style="color: var(--text-primary)">{{ $al['name'] }}</span>
                    <span class="text-wimf-400 font-mono text-xs">({{ $al['iata'] }})</span>
                </a>
            @endforeach
        </div>
        <h2 class="text-sm font-semibold mt-6 mb-3" style="color: var(--text-muted)">Search by Airport Code</h2>
        <div class="flex flex-wrap gap-2">
            @foreach(['DEL', 'BOM', 'BLR', 'JFK', 'LAX', 'LHR', 'DXB', 'SIN', 'CDG', 'FRA', 'HND', 'SFO'] as $code)
                <a href="{{ route('flights.search', ['q' => $code]) }}"
                   class="glass-card px-4 py-2 rounded-xl text-sm font-mono font-bold hover:scale-105 transition-all text-wimf-400">
                    {{ $code }}
                </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ═══════════════ AIRPORT INFO (when searching airport code) ═══════════════ --}}
    @if(isset($airportInfo) && $airportInfo)
    <div class="glass-card rounded-2xl p-5">
        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center space-x-3">
                    <span class="text-3xl font-black font-mono text-wimf-400">{{ $airportInfo['iata'] }}</span>
                    <div>
                        <h2 class="font-bold" style="color: var(--text-primary)">{{ $airportInfo['name'] }}</h2>
                        <p class="text-sm" style="color: var(--text-secondary)">{{ $airportInfo['city'] }}, {{ $airportInfo['country'] }}</p>
                    </div>
                </div>
            </div>
            <a href="{{ route('flights.board', ['airport' => $airportInfo['iata']]) }}"
               class="px-4 py-2 rounded-xl bg-wimf-600/20 text-wimf-400 text-sm font-medium hover:bg-wimf-600/30 transition-all">
                View Full Board →
            </a>
        </div>
        
        {{-- Category summary --}}
        @php
            $categories = ['ground' => 0, 'landing' => 0, 'departing' => 0, 'nearby' => 0];
            foreach($results as $r) { $categories[$r['category'] ?? 'nearby']++; }
        @endphp
        <div class="grid grid-cols-4 gap-3 mt-4">
            <div class="text-center p-3 rounded-xl" style="background: var(--bg-card)">
                <p class="text-lg font-black text-gray-400">{{ $categories['ground'] }}</p>
                <p class="text-xs" style="color: var(--text-muted)">🅿️ On Ground</p>
            </div>
            <div class="text-center p-3 rounded-xl" style="background: var(--bg-card)">
                <p class="text-lg font-black text-orange-400">{{ $categories['landing'] }}</p>
                <p class="text-xs" style="color: var(--text-muted)">🛬 Landing</p>
            </div>
            <div class="text-center p-3 rounded-xl" style="background: var(--bg-card)">
                <p class="text-lg font-black text-emerald-400">{{ $categories['departing'] }}</p>
                <p class="text-xs" style="color: var(--text-muted)">🛫 Departing</p>
            </div>
            <div class="text-center p-3 rounded-xl" style="background: var(--bg-card)">
                <p class="text-lg font-black text-wimf-400">{{ $categories['nearby'] }}</p>
                <p class="text-xs" style="color: var(--text-muted)">✈️ Nearby</p>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════ RESULTS ═══════════════ --}}
    @if($query)
        <div>
            <p class="text-sm mb-4" style="color: var(--text-muted)">
                {{ count($results) }} live aircraft found for
                <span class="font-mono text-wimf-400 font-bold">{{ $query }}</span>
                <span class="text-xs ml-2">• Real-time data from OpenSky Network</span>
            </p>

            @if(count($results) > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 stagger-children">
                    @foreach($results as $index => $flight)
                        @php
                            $airline = $flight['airline'] ?? getAirlineFromCallsign($flight['callsign']);
                            $status = $flight['status_info'] ?? getFlightStatus($flight);
                            $altColor = $flight['altitude'] > 10000 ? 'text-emerald-400' : ($flight['altitude'] > 5000 ? 'text-amber-400' : 'text-rose-400');
                        @endphp
                        <a href="{{ route('flight.profile', ['callsign' => trim($flight['callsign'])]) }}"
                           class="glass-card rounded-2xl p-5 hover:scale-[1.02] transition-all duration-300 block"
                           style="animation-delay: {{ ($index % 20) * 30 }}ms">
                            
                            {{-- Header --}}
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <h3 class="font-mono font-bold text-lg" style="color: var(--text-primary)">{{ $flight['callsign'] }}</h3>
                                        @if(!empty($airline['iata']))
                                            <span class="px-2 py-0.5 rounded-full bg-wimf-600/20 text-wimf-400 text-xs font-bold">
                                                {{ $airline['display'] ?? $airline['iata'] }}
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
                                    @if(!empty($flight['location_desc']))
                                        <p class="text-xs mt-1" style="color: var(--text-secondary)">📍 {{ $flight['location_desc'] }}</p>
                                    @endif
                                </div>
                                <div class="heading-indicator text-2xl" style="transform: rotate({{ $flight['heading'] }}deg)">✈</div>
                            </div>

                            {{-- Data Grid --}}
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <p class="text-xs" style="color: var(--text-muted)">Altitude</p>
                                    <p class="font-mono font-semibold {{ $altColor }}">{{ number_format($flight['altitude']) }}m</p>
                                </div>
                                <div>
                                    <p class="text-xs" style="color: var(--text-muted)">Speed</p>
                                    <p class="font-mono font-semibold text-wimf-400">{{ number_format(round($flight['velocity'] * 3.6)) }} km/h</p>
                                </div>
                            </div>

                            {{-- Footer --}}
                            <div class="mt-3 flex items-center justify-between">
                                <span class="px-2 py-0.5 rounded-full {{ $status['bg'] }} {{ $status['color'] }} text-xs font-medium">
                                    {{ $status['icon'] }} {{ $status['status'] }}
                                </span>
                                <span class="text-xs font-mono" style="color: var(--text-muted)">{{ $flight['icao24'] }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="text-center py-16 glass-card rounded-2xl">
                    <p class="text-lg" style="color: var(--text-muted)">No live aircraft found for "{{ $query }}"</p>
                    <p class="text-sm mt-2" style="color: var(--text-muted)">Try an airline name (IndiGo, Emirates), country (India), or airport code (DEL, JFK)</p>
                </div>
            @endif
        </div>
    @elseif(empty($query))
        <div class="text-center py-16">
            <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--text-muted); opacity: 0.4">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <p style="color: var(--text-muted)">Search by airline name, callsign, country, or airport code</p>
            <p class="text-xs mt-1" style="color: var(--text-muted)">e.g. "IndiGo", "Emirates", "India", "DEL", "JFK"</p>
        </div>
    @endif
</div>
@endsection
