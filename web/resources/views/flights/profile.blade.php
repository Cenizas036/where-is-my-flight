@extends('layouts.app')
@section('title', ($airline['name'] ?? '') . ' ' . $flight['callsign'] . ' — Flight Profile')

@section('content')
<div class="space-y-6 fade-in-up">

    {{-- ═══════════════ HERO — Aircraft Photo + Status ═══════════════ --}}
    <div class="glass-card rounded-3xl overflow-hidden">
        {{-- Photo Banner --}}
        <div class="relative h-56 md:h-72 bg-gradient-to-br from-wimf-600/30 to-purple-600/20 overflow-hidden">
            @if($aircraftPhoto)
                <img src="{{ $aircraftPhoto }}" alt="Aircraft Photo" 
                     class="w-full h-full object-cover opacity-80"
                     onerror="this.style.display='none'">
            @endif
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent"></div>
            
            {{-- Status Badge --}}
            <div class="absolute top-4 right-4">
                <span class="px-4 py-2 rounded-full {{ $status['bg'] }} {{ $status['color'] }} text-sm font-bold backdrop-blur-sm">
                    {{ $status['icon'] }} {{ $status['status'] }}
                </span>
            </div>

            {{-- Weather Badge --}}
            @if($weather)
            <div class="absolute top-4 left-4">
                <span class="px-4 py-2 rounded-full bg-black/40 text-white text-sm font-medium backdrop-blur-sm">
                    {{ $weather['icon'] }} {{ $weather['temp'] }}°C — {{ $weather['condition'] }}
                </span>
            </div>
            @endif

            {{-- Flight Info Overlay --}}
            <div class="absolute bottom-0 left-0 right-0 p-6">
                <div class="flex items-end justify-between">
                    <div>
                        <div class="flex items-center space-x-3 mb-2">
                            <h1 class="text-4xl font-black text-white">{{ $flight['callsign'] }}</h1>
                            @if(!empty($airline['iata']))
                                <span class="px-3 py-1 rounded-full bg-wimf-600/40 text-wimf-300 text-sm font-bold backdrop-blur-sm">
                                    {{ $airline['display'] ?? '' }}
                                </span>
                            @endif
                        </div>
                        @if(!empty($airline['name']))
                            <p class="text-xl text-wimf-300 font-semibold">{{ $airline['name'] }}</p>
                        @endif
                        <p class="text-sm text-gray-300 flex items-center gap-1">
                            {{ getCountryFlag($flight['origin_country']) }} {{ $flight['origin_country'] }}
                            <span class="mx-2">•</span>
                            <span class="text-gray-400">{{ $location }}</span>
                        </p>
                        @if($route)
                        <div class="mt-4 flex items-center gap-2 text-white flex-wrap">
                            <span class="text-sm font-semibold">From</span>
                            <div class="flex items-center gap-1 font-semibold text-sm bg-black/30 backdrop-blur-md px-3 py-1.5 rounded-xl border border-white/10">
                                🛫 {{ $route['origin']['city'] }} ({{ $route['origin']['iata'] }})
                            </div>
                            <span class="text-wimf-400 font-bold mx-1">to</span>
                            <div class="flex items-center gap-1 font-semibold text-sm bg-black/30 backdrop-blur-md px-3 py-1.5 rounded-xl border border-white/10">
                                🛬 {{ $route['destination']['city'] }} ({{ $route['destination']['iata'] }})
                            </div>
                        </div>
                        @else
                        <div class="mt-4 flex items-center gap-3 text-white">
                            <span class="text-xs text-gray-300 bg-black/30 px-3 py-1.5 rounded-xl border border-white/10">From unknown source to unknown destination</span>
                        </div>
                        @endif
                    </div>
                    <div class="text-right hidden md:block">
                        <p class="text-5xl font-black text-white heading-indicator" style="transform: rotate({{ $flight['heading'] - 45 }}deg)">✈</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Stats Bar --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-px" style="background: var(--border-card);">
            <div class="p-4 text-center" style="background: var(--bg-card)">
                <p class="text-xs" style="color: var(--text-muted)">Altitude</p>
                <p class="text-lg font-bold font-mono {{ $flight['altitude'] > 8000 ? 'text-emerald-400' : ($flight['altitude'] > 3000 ? 'text-amber-400' : 'text-rose-400') }}">
                    {{ number_format($flight['altitude']) }}m
                </p>
            </div>
            <div class="p-4 text-center" style="background: var(--bg-card)">
                <p class="text-xs" style="color: var(--text-muted)">Speed</p>
                <p class="text-lg font-bold font-mono text-wimf-400">{{ number_format(round($flight['velocity'] * 3.6)) }} km/h</p>
            </div>
            <div class="p-4 text-center" style="background: var(--bg-card)">
                <p class="text-xs" style="color: var(--text-muted)">Heading</p>
                @php $dirs = ['N','NE','E','SE','S','SW','W','NW']; $dir = $dirs[round($flight['heading']/45) % 8]; @endphp
                <p class="text-lg font-bold font-mono" style="color: var(--text-primary)">{{ $flight['heading'] }}° {{ $dir }}</p>
            </div>
            <div class="p-4 text-center" style="background: var(--bg-card)">
                <p class="text-xs" style="color: var(--text-muted)">V/Rate</p>
                @php $vrColor = $flight['vertical_rate'] > 0 ? 'text-emerald-400' : ($flight['vertical_rate'] < 0 ? 'text-rose-400' : ''); @endphp
                <p class="text-lg font-bold font-mono {{ $vrColor }}" style="{{ empty($vrColor) ? 'color: var(--text-muted)' : '' }}">
                    {{ $flight['vertical_rate'] > 0 ? '↑' : ($flight['vertical_rate'] < 0 ? '↓' : '—') }} {{ abs($flight['vertical_rate']) }} m/s
                </p>
            </div>
            <div class="p-4 text-center" style="background: var(--bg-card)">
                <p class="text-xs" style="color: var(--text-muted)">Squawk</p>
                <p class="text-lg font-bold font-mono" style="color: var(--text-primary)">{{ $flight['squawk'] ?? '—' }}</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ═══════════════ LEFT COLUMN ═══════════════ --}}
        <div class="space-y-6 lg:col-span-2">

            {{-- Satellite Map — Earthly terrain with weather --}}
            <div class="glass-card rounded-2xl overflow-hidden" style="box-shadow: 0 0 40px rgba(51,145,255,0.08), 0 0 80px rgba(16,185,129,0.04)">
                <div class="p-4 flex items-center justify-between" style="border-bottom: 1px solid var(--border-card)">
                    <div class="flex items-center space-x-2">
                        <div class="w-2.5 h-2.5 rounded-full bg-green-400 animate-pulse"></div>
                        <h2 class="font-bold" style="color: var(--text-primary)">🌍 Live Earth View</h2>
                    </div>
                    <div class="flex items-center space-x-3">
                        @if($weather)
                            <span class="text-xs px-2 py-1 rounded-lg" style="background: var(--bg-card); color: var(--text-secondary)">
                                {{ $weather['icon'] }} {{ $weather['temp'] }}°C {{ $weather['condition'] }}
                            </span>
                        @endif
                        <span class="text-xs font-mono" style="color: var(--text-muted)">{{ number_format($flight['latitude'], 4) }}, {{ number_format($flight['longitude'], 4) }}</span>
                    </div>
                </div>
                <div id="flight-map" style="height: 440px; width: 100%; position: relative;">
                    {{-- Atmosphere gradient overlay on map edges --}}
                    <div style="position:absolute;inset:0;pointer-events:none;z-index:999;
                        background: linear-gradient(180deg, rgba(0,0,0,0.15) 0%, transparent 12%, transparent 88%, rgba(0,0,0,0.15) 100%),
                                    linear-gradient(90deg, rgba(0,0,0,0.1) 0%, transparent 8%, transparent 92%, rgba(0,0,0,0.1) 100%);
                        border-radius: 0 0 1rem 1rem;"></div>
                </div>
            </div>

            {{-- Weather & Location --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Weather Card --}}
                <div class="glass-card rounded-2xl p-5">
                    <h3 class="text-sm font-bold mb-3" style="color: var(--text-muted)">🌤️ Weather at Position</h3>
                    @if($weather)
                        <div class="flex items-center space-x-3 mb-3">
                            <span class="text-4xl">{{ $weather['icon'] }}</span>
                            <div>
                                <p class="text-2xl font-black" style="color: var(--text-primary)">{{ $weather['temp'] }}°C</p>
                                <p class="text-sm {{ str_contains($weather['condition'], 'Clear') ? 'text-amber-400' : (str_contains($weather['condition'], 'Rain') ? 'text-blue-400' : '') }}" style="{{ !str_contains($weather['condition'], 'Clear') && !str_contains($weather['condition'], 'Rain') ? 'color: var(--text-secondary)' : '' }}">
                                    {{ $weather['condition'] }}
                                </p>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-2 text-xs">
                            <div class="p-2 rounded-lg" style="background: var(--bg-card)">
                                <p style="color: var(--text-muted)">Wind</p>
                                <p class="font-mono font-bold" style="color: var(--text-primary)">{{ $weather['wind_speed'] }} km/h</p>
                            </div>
                            <div class="p-2 rounded-lg" style="background: var(--bg-card)">
                                <p style="color: var(--text-muted)">Clouds</p>
                                <p class="font-mono font-bold" style="color: var(--text-primary)">{{ $weather['cloud_cover'] }}%</p>
                            </div>
                            <div class="p-2 rounded-lg" style="background: var(--bg-card)">
                                <p style="color: var(--text-muted)">Humidity</p>
                                <p class="font-mono font-bold" style="color: var(--text-primary)">{{ $weather['humidity'] }}%</p>
                            </div>
                        </div>
                    @else
                        <p class="text-sm" style="color: var(--text-muted)">Weather data unavailable</p>
                    @endif
                </div>

                {{-- Location --}}
                <div class="glass-card rounded-2xl p-5">
                    <h3 class="text-sm font-bold mb-3" style="color: var(--text-muted)">📍 Current Location</h3>
                    <p class="text-lg font-semibold" style="color: var(--text-primary)">{{ $location }}</p>
                    @if($nearestAirport)
                        <p class="text-sm mt-2" style="color: var(--text-secondary)">
                            Nearest Airport: <span class="font-mono font-bold text-wimf-400">{{ $nearestAirport['iata'] }}</span>
                            — {{ $nearestAirport['name'] }}
                            <span class="text-xs" style="color: var(--text-muted)">({{ $nearestAirport['distance_km'] }}km)</span>
                        </p>
                    @endif
                    <div class="mt-3 flex items-center space-x-2">
                        <span class="px-2 py-1 rounded-full {{ $status['bg'] }} {{ $status['color'] }} text-xs font-bold">
                            {{ $status['icon'] }} {{ $status['status'] }}
                        </span>
                        <span class="text-xs" style="color: var(--text-muted)">{{ $status['desc'] }}</span>
                    </div>
                    @if($flight['on_ground'] && $nearestAirport && $nearestAirport['distance_km'] < 5)
                        <p class="text-xs mt-2 text-amber-400">🅿️ Parked at {{ $nearestAirport['iata'] }} — may depart soon</p>
                    @endif
                </div>
            </div>

            {{-- Gate --}}
            <div class="glass-card rounded-2xl p-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-bold" style="color: var(--text-muted)">🚪 Gate Assignment</span>
                    @if(isset($dbFlight) && $dbFlight)
                        @if($dbFlight->departure_gate || $dbFlight->arrival_gate)
                            <span class="px-3 py-1 rounded-full bg-wimf-500/20 text-wimf-300 text-sm font-bold font-mono border border-wimf-500/30">Gate {{ $dbFlight->departure_gate ?? $dbFlight->arrival_gate }}</span>
                        @else
                            <a href="{{ route('gates.edit', $dbFlight->id) }}" class="text-xs font-bold text-wimf-400 hover:text-white transition-colors bg-wimf-500/20 px-3 py-1 rounded-full border border-wimf-500/30 shadow-lg shadow-wimf-500/20">Assign Gate</a>
                        @endif
                    @else
                        <span class="px-2 py-0.5 rounded-full bg-gray-500/20 text-gray-400 text-xs font-mono" title="Requires scheduled DB flight">N/A</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- ═══════════════ RIGHT COLUMN ═══════════════ --}}
        <div class="space-y-6">

            {{-- AI Delay Prediction --}}
            <div class="glass-card rounded-2xl p-5 relative overflow-hidden" style="border: 1px solid {{ $prediction['probability'] > 40 ? 'rgba(244, 63, 94, 0.4)' : 'var(--border-card)' }}; box-shadow: 0 0 30px {{ $prediction['probability'] > 40 ? 'rgba(244, 63, 94, 0.1)' : 'rgba(51, 145, 255, 0.05)' }}">
                {{-- Background Glow --}}
                <div class="absolute -top-10 -right-10 w-32 h-32 rounded-full blur-3xl opacity-20 {{ $prediction['probability'] > 40 ? 'bg-rose-500' : 'bg-emerald-500' }}"></div>
                
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold" style="color: var(--text-muted)">🤖 AI Delay Prediction</h3>
                    <span class="px-2 py-1 rounded text-xs font-bold font-mono" style="background: var(--bg-card); color: var(--text-secondary)">Model v2.4</span>
                </div>

                <div class="flex items-end justify-between mb-2">
                    <div>
                        <p class="text-3xl font-black {{ $prediction['probability'] > 40 ? 'text-rose-400' : 'text-emerald-400' }}">{{ $prediction['probability'] }}%</p>
                        <p class="text-xs font-medium" style="color: var(--text-secondary)">Probability of Delay</p>
                    </div>
                    @if($prediction['estimated_min'] > 0)
                        <div class="text-right">
                            <p class="text-2xl font-bold font-mono text-amber-400">+{{ $prediction['estimated_min'] }}m</p>
                            <p class="text-xs" style="color: var(--text-secondary)">Est. Delay</p>
                        </div>
                    @endif
                </div>

                {{-- Progress Bar --}}
                <div class="w-full h-2 bg-gray-800 rounded-full mt-3 overflow-hidden">
                    <div class="h-full rounded-full {{ $prediction['probability'] > 40 ? 'bg-gradient-to-r from-rose-500 to-rose-400' : 'bg-gradient-to-r from-emerald-500 to-emerald-400' }}" style="width: {{ $prediction['probability'] }}%;"></div>
                </div>

                @if($prediction['cause'] !== 'None')
                    <div class="mt-4 p-3 rounded-lg flex items-start space-x-2" style="background: rgba(0,0,0,0.2)">
                        <span class="text-sm mt-0.5">⚠️</span>
                        <div>
                            <p class="text-xs font-bold" style="color: var(--text-primary)">Identified Risk Factor:</p>
                            <p class="text-sm" style="color: var(--text-secondary)">{{ $prediction['cause'] }}</p>
                        </div>
                    </div>
                @else
                    <div class="mt-4 p-3 rounded-lg flex items-start space-x-2" style="background: rgba(0,0,0,0.2)">
                        <span class="text-sm mt-0.5">✅</span>
                        <div>
                            <p class="text-xs font-bold text-emerald-400">Flight operating normally</p>
                            <p class="text-sm" style="color: var(--text-secondary)">No significant delay risks identified.</p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Aircraft Details --}}
            <div class="glass-card rounded-2xl p-5">
                <h3 class="text-sm font-bold mb-4" style="color: var(--text-muted)">🛩️ Aircraft Details</h3>
                @if($aircraftInfo)
                    <div class="space-y-3">
                        @foreach([
                            ['Registration', $aircraftInfo['registration'], true],
                            ['Manufacturer', $aircraftInfo['manufacturer'], false],
                            ['Aircraft Type', $aircraftInfo['type'], false],
                            ['ICAO Type', $aircraftInfo['type_code'], false],
                            ['Owner', $aircraftInfo['owner'], false],
                            ['ICAO24', $flight['icao24'], false],
                        ] as [$label, $value, $accent])
                            <div class="flex justify-between">
                                <span class="text-sm" style="color: var(--text-secondary)">{{ $label }}</span>
                                <span class="{{ $accent ? 'font-mono font-bold text-wimf-400' : '' }}" style="{{ !$accent ? 'color: var(--text-primary)' : '' }}">{{ $value }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-4 pt-4 border-t flex flex-col gap-2" style="border-color: var(--border)">
                        <h4 class="text-xs font-bold uppercase tracking-wider mb-1" style="color:var(--text-muted)">Media Feeds</h4>
                        <a href="https://www.flightradar24.com/{{ $flight['callsign'] }}" target="_blank" rel="noopener"
                           class="flex justify-between items-center px-4 py-2.5 rounded-xl hover:scale-105 transition-all text-sm font-semibold mt-1"
                           style="background: var(--bg-card); border: 1px solid var(--border); color: var(--text-primary)">
                            <span>🛩️ Open in FlightRadar24</span>
                            <span style="color:var(--text-muted)">→</span>
                        </a>
                    </div>
                @else
                    <p class="text-sm" style="color: var(--text-muted)">Aircraft details not available for ICAO24: {{ $flight['icao24'] }}</p>
                @endif
            </div>
            </div>

            {{-- Book This Flight --}}
            <div class="glass-card rounded-2xl p-5">
                <h3 class="text-sm font-bold mb-4" style="color: var(--text-muted)">🎫 Book Similar Flights</h3>
                <div class="space-y-2">
                    @foreach(array_slice($bookingLinks, 0, 4) as $link)
                        <a href="{{ $link['url'] }}" target="_blank" rel="noopener"
                           class="flex items-center justify-between p-3 rounded-xl hover:scale-[1.02] transition-all"
                           style="background: var(--bg-card); border: 1px solid var(--border-card)">
                            <div class="flex items-center space-x-3">
                                <span class="text-xl">{{ $link['icon'] }}</span>
                                <span class="font-medium text-sm" style="color: var(--text-primary)">{{ $link['name'] }}</span>
                            </div>
                            <span class="text-xs text-wimf-400">Open →</span>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex flex-col gap-2">
                <a href="{{ route('flights.booking', ['callsign' => $flight['callsign']]) }}" 
                   class="block w-full px-4 py-3 rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-700 text-white text-sm font-semibold text-center hover:scale-105 transition-all">
                    🎫 Book This Route
                </a>
                <a href="{{ route('flights.search', ['q' => $airline['name'] ?? $flight['callsign']]) }}" 
                   class="block w-full px-4 py-3 rounded-xl text-sm font-semibold text-center hover:scale-105 transition-all"
                   style="background: var(--bg-card); border: 1px solid var(--border-card); color: var(--text-primary)">
                    🔍 More {{ $airline['name'] ?? '' }} Flights
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const map = L.map('flight-map', { zoomControl: false }).setView([{{ $flight['latitude'] }}, {{ $flight['longitude'] }}], 7);
    L.control.zoom({ position: 'bottomright' }).addTo(map);

    // ── Base tile layers — earthly, realistic look ──
    const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: '© Esri', maxZoom: 19
    });
    const natGeoTerrain = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/NatGeo_World_Map/MapServer/tile/{z}/{y}/{x}', {
        attribution: '© Esri / National Geographic', maxZoom: 16
    });
    const esriTerrain = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}', {
        attribution: '© Esri', maxZoom: 18
    });
    const physicalEarth = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Physical_Map/MapServer/tile/{z}/{y}/{x}', {
        attribution: '© Esri', maxZoom: 8
    });
    const labels = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', {
        attribution: '', maxZoom: 19, opacity: 0.85
    });
    const reliefShading = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Shaded_Relief/MapServer/tile/{z}/{y}/{x}', {
        attribution: '© Esri', maxZoom: 13, opacity: 0.35
    });

    // ── Weather overlay layers — for that atmospheric, alive feel ──
    const cloudsOverlay = L.tileLayer('https://tile.openweathermap.org/map/clouds_new/{z}/{x}/{y}.png?appid=9de243494c0b295cca9337e1e96b00e2', {
        attribution: '© OWM', maxZoom: 19, opacity: 0.5
    });
    const precipOverlay = L.tileLayer('https://tile.openweathermap.org/map/precipitation_new/{z}/{x}/{y}.png?appid=9de243494c0b295cca9337e1e96b00e2', {
        attribution: '© OWM', maxZoom: 19, opacity: 0.55
    });
    const tempOverlay = L.tileLayer('https://tile.openweathermap.org/map/temp_new/{z}/{x}/{y}.png?appid=9de243494c0b295cca9337e1e96b00e2', {
        attribution: '© OWM', maxZoom: 19, opacity: 0.3
    });
    const windOverlay = L.tileLayer('https://tile.openweathermap.org/map/wind_new/{z}/{x}/{y}.png?appid=9de243494c0b295cca9337e1e96b00e2', {
        attribution: '© OWM', maxZoom: 19, opacity: 0.35
    });
    const pressOverlay = L.tileLayer('https://tile.openweathermap.org/map/pressure_new/{z}/{x}/{y}.png?appid=9de243494c0b295cca9337e1e96b00e2', {
        attribution: '© OWM', maxZoom: 19, opacity: 0.25
    });

    // Default: Satellite imagery + labels + clouds + precipitation for earthly weather feel
    satellite.addTo(map);
    labels.addTo(map);
    cloudsOverlay.addTo(map);
    precipOverlay.addTo(map);

    // Layer control
    L.control.layers({
        '🛰️ Satellite Earth': satellite,
        '🌍 Terrain Relief': esriTerrain,
        '🗺️ National Geographic': natGeoTerrain,
        '🏔️ Physical Earth': physicalEarth,
    }, {
        '🏷️ Place Names': labels,
        '⛰️ Relief Shading': reliefShading,
        '☁️ Cloud Cover': cloudsOverlay,
        '🌧️ Precipitation': precipOverlay,
        '🌡️ Temperature': tempOverlay,
        '💨 Wind Speed': windOverlay,
        '🔵 Pressure': pressOverlay,
    }, { position: 'topright', collapsed: true }).addTo(map);

    // Flight marker with glowing aura trail
    const planeIcon = L.divIcon({
        html: `<div style="position:relative">
            <div style="position:absolute;top:-12px;left:-12px;width:58px;height:58px;border-radius:50%;background:radial-gradient(circle,rgba(51,145,255,0.5) 0%,rgba(51,145,255,0.15) 40%,transparent 70%);animation:pulse 2s ease-in-out infinite"></div>
            <div style="position:absolute;top:-4px;left:-4px;width:42px;height:42px;border-radius:50%;background:radial-gradient(circle,rgba(51,145,255,0.25) 0%,transparent 60%);animation:pulse 2s ease-in-out infinite 0.3s"></div>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#ffffff" style="width:34px;height:34px;transform:rotate({{ ($flight['heading'] ?? 0) }}deg);filter:drop-shadow(0 0 10px rgba(51,145,255,0.9));position:relative;z-index:9999;">
              <path d="M21 16v-2l-8-5V3.5C13 2.67 12.33 2 11.5 2S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z"/>
            </svg>
        </div>`,
        iconSize: [34, 34], iconAnchor: [17, 17], className: ''
    });
    const markerPopupHtml = `
            <div style="font-family:'Inter',sans-serif;font-size:13px;min-width:240px;line-height:1.7;padding:4px 0">
                <b style="font-size:16px;color:#3391ff">{{ $flight['callsign'] }}</b>
                {!! !empty($airline['name']) ? '<span style="color:#888"> — '.$airline['name'].'</span>' : '' !!}<br>
                {{ $status['icon'] }} <b>{{ $status['status'] }}</b><br>
                📍 <span style="color:#64748b">{{ $location }}</span><br>
                🔺 {{ number_format($flight['altitude']) }}m &nbsp;⚡ {{ number_format(round($flight['velocity']*3.6)) }}km/h &nbsp;
                🧭 {{ $flight['heading'] }}°<br>
                @if($weather)
                <div style="margin-top:6px;padding:6px 8px;background:#f0f9ff;border-radius:6px;font-size:12px;color:#333">
                    {{ $weather['icon'] }} <b>{{ $weather['temp'] }}°C</b> — {{ $weather['condition'] }}
                    &nbsp;💨 {{ $weather['wind_speed'] }}km/h &nbsp;☁ {{ $weather['cloud_cover'] }}%
                </div>
                @endif
            </div>
        `;

    let markerRef = L.marker([{{ $flight['latitude'] }}, {{ $flight['longitude'] }}], { icon: planeIcon, zIndexOffset: 99999 })
        .addTo(map)
        .bindPopup(markerPopupHtml)
        .openPopup();
        
    // ── DRAW LINE TO DESTINATION (OR PREDICTED FORWARD TRAJECTORY) ──
    @php
        $destCoords = null;
        if (!empty($route['destination']['iata'])) {
            $destCoords = getAirportCoords($route['destination']['iata']);
        }
    @endphp

    @if($destCoords)
        const projLat = {{ $destCoords['lat'] }} * Math.PI / 180;
        const projLon = {{ $destCoords['lon'] }} * Math.PI / 180;
    @else
        const earthRadius = 6371000;
        const currentHeading = {{ ($flight['heading'] ?? 0) }};
        const brngForward = currentHeading * Math.PI / 180; // Point forward!
        const projDist = 120000 / earthRadius; // 120km forward projection
        const lat1R = {{ $flight['latitude'] }} * Math.PI / 180;
        const lon1R = {{ $flight['longitude'] }} * Math.PI / 180;
        const projLat = Math.asin(Math.sin(lat1R) * Math.cos(projDist) + Math.cos(lat1R) * Math.sin(projDist) * Math.cos(brngForward));
        const projLon = lon1R + Math.atan2(Math.sin(brngForward) * Math.sin(projDist) * Math.cos(lat1R), Math.cos(projDist) - Math.sin(lat1R) * Math.sin(projLat));
    @endif

    // Flight path projected line
    let dashedLine = L.polyline([
        [{{ $flight['latitude'] }}, {{ $flight['longitude'] }}],
        [projLat * 180 / Math.PI, projLon * 180 / Math.PI]
    ], { color: '#3391ff', weight: 4, dashArray: '10, 10', opacity: 0.7 }).addTo(map);
    
    let glowLine = L.polyline([
        [{{ $flight['latitude'] }}, {{ $flight['longitude'] }}],
        [projLat * 180 / Math.PI, projLon * 180 / Math.PI]
    ], { color: '#3391ff', weight: 8, opacity: 0.15 }).addTo(map);
        
    // ── LIVE DEAD-RECKONING MOVEMENT ──
    let currentLat = {{ $flight['latitude'] }};
    let currentLon = {{ $flight['longitude'] }};
    const speedMs = {{ $flight['velocity'] }}; // m/s
    
    // Only animate if the plane is airborne and moving
    if (speedMs > 5 && !{{ $flight['on_ground'] ? 'true' : 'false' }}) {
        setInterval(() => {
            try {
                // Distance traveled in 100ms (0.1 seconds)
                const distance = speedMs * 0.1; 
                const distRatio = distance / earthRadius;
                const brng = currentHeading * Math.PI / 180;
                const lat1 = currentLat * Math.PI / 180;
                const lon1 = currentLon * Math.PI / 180;

                const lat2 = Math.asin(Math.sin(lat1) * Math.cos(distRatio) + Math.cos(lat1) * Math.sin(distRatio) * Math.cos(brng));
                const lon2 = lon1 + Math.atan2(Math.sin(brng) * Math.sin(distRatio) * Math.cos(lat1), Math.cos(distRatio) - Math.sin(lat1) * Math.sin(lat2));

                if (!isNaN(lat2) && !isNaN(lon2)) {
                    currentLat = lat2 * 180 / Math.PI;
                    currentLon = lon2 * 180 / Math.PI;
                    
                    markerRef.setLatLng([currentLat, currentLon]);

                    if (typeof dashedLine !== 'undefined' && typeof glowLine !== 'undefined') {
                        const updatedProj = [projLat * 180 / Math.PI, projLon * 180 / Math.PI];
                        dashedLine.setLatLngs([[currentLat, currentLon], updatedProj]);
                        glowLine.setLatLngs([[currentLat, currentLon], updatedProj]);
                    }
                }
            } catch (e) {
                console.error("Live anim error:", e);
            }
        }, 100); // 10 FPS smooth animation
    }

    @if($destCoords)
    // Destination Airport marker — glowing green beacon
    const airportIcon = L.divIcon({
        html: `<div style="position:relative">
            <div style="position:absolute;top:-6px;left:-6px;width:32px;height:32px;border-radius:50%;background:radial-gradient(circle,rgba(16,185,129,0.4) 0%,transparent 70%);animation:pulse 2.5s ease-in-out infinite"></div>
            <div style="width:20px;height:20px;background:#10b981;border:3px solid white;border-radius:50%;box-shadow:0 0 16px rgba(16,185,129,0.7);position:relative;z-index:2"></div>
        </div>`,
        iconSize: [20,20], iconAnchor: [10,10], className: ''
    });
    L.marker([{{ $destCoords['lat'] }}, {{ $destCoords['lon'] }}], { icon: airportIcon })
        .addTo(map).bindPopup(`<b style="font-size:15px;color:#10b981">🛬 {{ $route['destination']['iata'] }}</b><br><span style="color:#555">{{ $route['destination']['city'] }}</span><br><span style="color:#888;font-size:11px">Destination</span>`);
    @else
    // Projected Trajectory marker — glowing green beacon (fallback)
    const airportIcon = L.divIcon({
        html: `<div style="position:relative">
            <div style="position:absolute;top:-6px;left:-6px;width:32px;height:32px;border-radius:50%;background:radial-gradient(circle,rgba(16,185,129,0.4) 0%,transparent 70%);animation:pulse 2.5s ease-in-out infinite"></div>
            <div style="width:20px;height:20px;background:#10b981;border:3px solid white;border-radius:50%;box-shadow:0 0 16px rgba(16,185,129,0.7);position:relative;z-index:2"></div>
        </div>`,
        iconSize: [20,20], iconAnchor: [10,10], className: ''
    });
    L.marker([projLat * 180 / Math.PI, projLon * 180 / Math.PI], { icon: airportIcon })
        .addTo(map).bindPopup(`<b style="font-size:15px;color:#10b981">Projected Path</b><br><span style="color:#555">Forward trajectory projection</span>`);
    @endif

    map.fitBounds([
        [{{ $flight['latitude'] }}, {{ $flight['longitude'] }}],
        [projLat * 180 / Math.PI, projLon * 180 / Math.PI]
    ], { padding: [70, 70] });
    // CSS for pulsing marker
    const style = document.createElement('style');
    style.textContent = `
        @keyframes pulse{0%,100%{transform:scale(1);opacity:0.6}50%{transform:scale(1.6);opacity:0}}
        .leaflet-control-layers { border-radius: 12px !important; backdrop-filter: blur(12px); }
    `;
    document.head.appendChild(style);
</script>
@endpush
