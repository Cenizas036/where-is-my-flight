@extends('layouts.app')
@section('title', 'My Dashboard — Flights Near Me')

@section('content')
<div class="space-y-6 fade-in-up">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold gradient-text">My Dashboard</h1>
            <p class="text-sm mt-1" style="color: var(--text-secondary)">
                Track flights in your vicinity with real-time ADS-B data
            </p>
        </div>
        <button onclick="requestLocation()" id="loc-btn"
                class="px-5 py-3 rounded-xl bg-gradient-to-r from-wimf-600 to-wimf-700 text-white font-semibold text-sm hover:scale-105 transition-all flex items-center space-x-2">
            <span>📍</span>
            <span>Enable Location</span>
        </button>
    </div>

    {{-- Location Status --}}
    <div id="location-status" class="glass-card rounded-2xl p-5 hidden">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <span class="w-3 h-3 rounded-full bg-green-400 animate-pulse"></span>
                <div>
                    <p class="font-semibold text-sm" style="color: var(--text-primary)" id="loc-text">Locating...</p>
                    <p class="text-xs" style="color: var(--text-muted)" id="loc-coords"></p>
                </div>
            </div>
            <div id="nearest-airport-badge" class="hidden">
                <span class="px-3 py-1 rounded-full bg-wimf-600/20 text-wimf-400 text-sm font-mono font-bold" id="nearest-iata"></span>
            </div>
        </div>
    </div>

    {{-- Map --}}
    <div class="glass-card rounded-2xl overflow-hidden" style="box-shadow: 0 0 40px rgba(51,145,255,0.08), 0 0 80px rgba(16,185,129,0.04)">
        <div class="p-4 flex items-center justify-between" style="border-bottom: 1px solid var(--border-card)">
            <div class="flex items-center space-x-2">
                <div class="w-2.5 h-2.5 rounded-full bg-green-400 animate-pulse"></div>
                <h2 class="font-bold" style="color: var(--text-primary)">🌍 Live Earth View — Flights Near You</h2>
            </div>
            <div class="flex items-center space-x-3">
                <span class="text-xs font-mono" style="color: var(--text-muted)" id="flight-count">—</span>
                <button onclick="refreshNearby()" class="px-3 py-1 rounded-lg text-xs font-medium bg-wimf-600/20 text-wimf-400 hover:bg-wimf-600/30 transition-all">
                    🔄 Refresh
                </button>
            </div>
        </div>
        <div id="dashboard-map" style="height: 500px; width: 100%; position: relative;">
            <div style="position:absolute;inset:0;pointer-events:none;z-index:999;
                background: linear-gradient(180deg, rgba(0,0,0,0.12) 0%, transparent 10%, transparent 90%, rgba(0,0,0,0.12) 100%),
                            linear-gradient(90deg, rgba(0,0,0,0.08) 0%, transparent 6%, transparent 94%, rgba(0,0,0,0.08) 100%);
                border-radius: 0 0 1rem 1rem;"></div>
        </div>
    </div>

    {{-- Nearest Airport --}}
    <div id="airport-section" class="hidden">
        <div class="glass-card rounded-2xl p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="font-bold text-lg" style="color: var(--text-primary)">
                        🏢 <span id="airport-name">Nearest Airport</span>
                        <span class="px-2 py-0.5 rounded-full bg-wimf-600/20 text-wimf-400 text-sm font-mono font-bold ml-2" id="airport-iata"></span>
                    </h2>
                    <p class="text-xs mt-1" style="color: var(--text-muted)" id="airport-distance"></p>
                </div>
                <a id="airport-board-link" href="#" class="px-4 py-2 rounded-xl text-sm font-medium bg-wimf-600/20 text-wimf-400 hover:bg-wimf-600/30 transition-all">
                    View Board →
                </a>
            </div>
        </div>
    </div>

    {{-- Nearby Flights --}}
    <div id="flights-section" class="hidden">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold text-lg" style="color: var(--text-primary)">✈ Aircraft In Your Area</h2>
            <span class="text-xs" style="color: var(--text-muted)" id="total-nearby">Loading...</span>
        </div>
        <div id="flights-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 stagger-children"></div>
    </div>

    {{-- No Location --}}
    <div id="no-location" class="text-center py-16 glass-card rounded-2xl">
        <div class="text-6xl mb-4">📍</div>
        <h2 class="text-xl font-bold mb-2" style="color: var(--text-primary)">Enable Location to See Nearby Flights</h2>
        <p class="text-sm mb-6" style="color: var(--text-secondary)">
            Click the "Enable Location" button above to share your position.<br>
            We'll show you all aircraft flying in your area in real-time on a satellite map.
        </p>
        <button onclick="requestLocation()" 
                class="px-8 py-3 rounded-xl bg-gradient-to-r from-wimf-600 to-wimf-700 text-white font-semibold hover:scale-105 transition-all">
            📍 Share My Location
        </button>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="glass-card rounded-2xl p-5 text-center">
            <p class="text-3xl font-black text-wimf-400" id="stat-flights">—</p>
            <p class="text-xs mt-1" style="color: var(--text-muted)">Aircraft Nearby</p>
        </div>
        <div class="glass-card rounded-2xl p-5 text-center">
            <p class="text-3xl font-black text-emerald-400" id="stat-ground">—</p>
            <p class="text-xs mt-1" style="color: var(--text-muted)">On Ground</p>
        </div>
        <div class="glass-card rounded-2xl p-5 text-center">
            <p class="text-3xl font-black text-amber-400" id="stat-closest">—</p>
            <p class="text-xs mt-1" style="color: var(--text-muted)">Closest Aircraft (km)</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    let userLat = null, userLon = null, map = null, markers = [];

    function initMap(lat, lon) {
        if (map) map.remove();
        map = L.map('dashboard-map', { zoomControl: false }).setView([lat, lon], 10);
        L.control.zoom({ position: 'bottomright' }).addTo(map);
        
        // Base layers — earthly, realistic look
        const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: '© Esri', maxZoom: 19
        }).addTo(map);
        const esriTerrain = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}', {
            attribution: '© Esri', maxZoom: 18
        });
        const natGeo = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/NatGeo_World_Map/MapServer/tile/{z}/{y}/{x}', {
            attribution: '© NatGeo', maxZoom: 16
        });
        const labels = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', {
            attribution: '', maxZoom: 19, opacity: 0.85
        }).addTo(map);

        // Weather overlays — atmospheric, alive feel
        const cloudsOverlay = L.tileLayer('https://tile.openweathermap.org/map/clouds_new/{z}/{x}/{y}.png?appid=9de243494c0b295cca9337e1e96b00e2', {
            maxZoom: 19, opacity: 0.5
        }).addTo(map);
        const precipOverlay = L.tileLayer('https://tile.openweathermap.org/map/precipitation_new/{z}/{x}/{y}.png?appid=9de243494c0b295cca9337e1e96b00e2', {
            maxZoom: 19, opacity: 0.5
        }).addTo(map);
        const windOverlay = L.tileLayer('https://tile.openweathermap.org/map/wind_new/{z}/{x}/{y}.png?appid=9de243494c0b295cca9337e1e96b00e2', {
            maxZoom: 19, opacity: 0.35
        });
        const tempOverlay = L.tileLayer('https://tile.openweathermap.org/map/temp_new/{z}/{x}/{y}.png?appid=9de243494c0b295cca9337e1e96b00e2', {
            maxZoom: 19, opacity: 0.3
        });

        L.control.layers({
            '🛰️ Satellite Earth': satellite,
            '🌍 Terrain': esriTerrain,
            '🗺️ National Geographic': natGeo,
        }, {
            '🏷️ Labels': labels,
            '☁️ Clouds': cloudsOverlay,
            '🌧️ Rain': precipOverlay,
            '💨 Wind': windOverlay,
            '🌡️ Temperature': tempOverlay,
        }, { position: 'topright', collapsed: true }).addTo(map);

        // Atmosphere styling for layer controls
        const mapStyle = document.createElement('style');
        mapStyle.textContent = '.leaflet-control-layers { border-radius: 12px !important; backdrop-filter: blur(12px); }';
        document.head.appendChild(mapStyle);

        // User marker with pulsing ring
        const userIcon = L.divIcon({
            html: `<div style="position:relative">
                <div style="width:16px;height:16px;background:#3391ff;border:3px solid white;border-radius:50%;box-shadow:0 0 12px rgba(51,145,255,0.6)"></div>
                <div style="position:absolute;top:-8px;left:-8px;width:32px;height:32px;border:2px solid rgba(51,145,255,0.4);border-radius:50%;animation:pulse 2s infinite"></div>
            </div>`,
            iconSize: [16,16], iconAnchor: [8,8], className: ''
        });
        L.marker([lat, lon], { icon: userIcon }).addTo(map).bindPopup('<b>📍 You are here</b>');
    }

    function requestLocation() {
        if (!navigator.geolocation) { alert('Geolocation not supported'); return; }
        const btn = document.getElementById('loc-btn');
        btn.innerHTML = '<span>⏳</span><span>Locating...</span>';
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                userLat = pos.coords.latitude;
                userLon = pos.coords.longitude;
                btn.innerHTML = '<span>✅</span><span>Location Active</span>';
                document.getElementById('location-status').classList.remove('hidden');
                document.getElementById('loc-text').textContent = 'Location acquired';
                document.getElementById('loc-coords').textContent = `${userLat.toFixed(4)}, ${userLon.toFixed(4)}`;
                document.getElementById('no-location').classList.add('hidden');
                document.getElementById('flights-section').classList.remove('hidden');
                initMap(userLat, userLon);
                refreshNearby();
            },
            () => {
                btn.innerHTML = '<span>❌</span><span>Location Denied</span>';
                alert('Location access denied.');
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    }

    function refreshNearby() {
        if (!userLat || !userLon) return;
        fetch(`/api/nearby-flights?lat=${userLat}&lon=${userLon}`)
            .then(r => r.json())
            .then(data => {
                renderFlights(data.flights || []);
                renderMap(data.flights || [], data.nearest_airport);
                renderAirport(data.nearest_airport);
                updateStats(data.flights || []);
            })
            .catch(err => console.error('Nearby fetch error:', err));
    }

    function renderMap(flights, airport) {
        markers.forEach(m => map.removeLayer(m));
        markers = [];

        if (airport) {
            const am = L.circleMarker([airport.lat, airport.lon], {
                radius: 14, color: '#10b981', fillColor: '#10b981', fillOpacity: 0.5, weight: 2
            }).addTo(map).bindPopup(`<b>${airport.iata}</b><br>${airport.name}`);
            markers.push(am);
        }

        flights.forEach(f => {
            const pi = L.divIcon({
                html: `<div style="font-size:20px;transform:rotate(${f.heading - 45}deg);filter:drop-shadow(0 2px 4px rgba(0,0,0,0.8));cursor:pointer" title="${f.callsign}">✈️</div>`,
                iconSize: [22,22], iconAnchor: [11,11], className: ''
            });
            const m = L.marker([f.latitude, f.longitude], { icon: pi })
                .addTo(map)
                .bindPopup(`
                    <div style="font-family:'Inter',sans-serif;font-size:12px;min-width:220px;line-height:1.7;padding:2px 0">
                        <b style="font-size:15px;color:#3391ff">${f.callsign}</b> ${f.airline_name ? '<span style="color:#888; font-weight:normal"> — '+f.airline_name+'</span>' : ''}<br>
                        ${f.status_icon} <b>${f.status_text}</b><br>
                        📍 <span style="color:#64748b">${f.location_desc || 'Unknown location'}</span><br>
                        🔺 ${f.altitude.toLocaleString()}m &nbsp;⚡ ${f.speed_kmh}km/h<br>
                        <span style="color:#888;font-size:11px">${f.distance_km}km away from you</span><br>
                        <div style="margin-top:4px;display:flex;gap:8px">
                            <a href="/flight/${f.callsign.trim()}" style="color:#3391ff;font-weight:600;text-decoration:none">Details →</a>
                            <a href="/book/${f.callsign.trim()}" style="color:#10b981;font-weight:600;text-decoration:none">Book →</a>
                        </div>
                    </div>
                `);
            markers.push(m);
        });
        document.getElementById('flight-count').textContent = `${flights.length} aircraft`;
    }

    function renderFlights(flights) {
        const grid = document.getElementById('flights-grid');
        grid.innerHTML = flights.slice(0, 20).map((f, i) => `
            <a href="/flight/${f.callsign.trim()}" class="glass-card rounded-2xl p-4 hover:scale-[1.02] transition-all block" style="animation-delay:${i*30}ms">
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <h3 class="font-mono font-bold" style="color:var(--text-primary)">${f.callsign}</h3>
                        ${f.airline_name ? `<p class="text-xs text-wimf-400">${f.airline_name}</p>` : ''}
                    </div>
                    <div style="font-size:18px;transform:rotate(${f.heading - 45}deg)" class="heading-indicator">✈</div>
                </div>
                ${f.location_desc ? `<p class="text-xs mb-2" style="color:var(--text-secondary)">📍 ${f.location_desc}</p>` : ''}
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div>
                        <span style="color:var(--text-muted)">Distance</span>
                        <p class="font-mono font-bold text-wimf-400">${f.distance_km}km</p>
                    </div>
                    <div>
                        <span style="color:var(--text-muted)">Altitude</span>
                        <p class="font-mono font-bold ${f.altitude > 8000 ? 'text-emerald-400' : 'text-amber-400'}">${f.altitude.toLocaleString()}m</p>
                    </div>
                    <div>
                        <span style="color:var(--text-muted)">Speed</span>
                        <p class="font-mono font-bold" style="color:var(--text-secondary)">${f.speed_kmh}km/h</p>
                    </div>
                    <div>
                        <span style="color:var(--text-muted)">Status</span>
                        <p class="font-bold text-xs">${f.status_icon} ${f.status_text}</p>
                    </div>
                </div>
            </a>
        `).join('');
        document.getElementById('total-nearby').textContent = `${flights.length} aircraft in range`;
    }

    function renderAirport(airport) {
        if (!airport) return;
        document.getElementById('airport-section').classList.remove('hidden');
        document.getElementById('airport-name').textContent = airport.name;
        document.getElementById('airport-iata').textContent = airport.iata;
        document.getElementById('airport-distance').textContent = `${airport.distance_km}km from you • ${airport.city}, ${airport.country}`;
        document.getElementById('airport-board-link').href = `/board/${airport.iata}`;
        document.getElementById('nearest-airport-badge').classList.remove('hidden');
        document.getElementById('nearest-iata').textContent = airport.iata;
    }

    function updateStats(flights) {
        document.getElementById('stat-flights').textContent = flights.length;
        document.getElementById('stat-ground').textContent = flights.filter(f => f.on_ground).length;
        document.getElementById('stat-closest').textContent = flights.length > 0 ? flights[0].distance_km : '—';
    }

    setInterval(() => { if (userLat) refreshNearby(); }, 30000);
</script>
@endpush
