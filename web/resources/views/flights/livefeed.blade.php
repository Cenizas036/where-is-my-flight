@extends('layouts.app')
@section('title', 'Live Global Flight Feed — Real-Time Aircraft')

@section('content')
<div class="space-y-8 fade-in-up">

    {{-- ═══════════════ LIVE STATS BAR ═══════════════ --}}
    <div class="glass-card rounded-2xl p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center space-x-3">
                <div class="w-3 h-3 rounded-full bg-green-400 animate-pulse shadow-lg shadow-green-400/50"></div>
                <h1 class="text-2xl font-bold gradient-text">Live Global Flight Feed</h1>
            </div>
            <div class="flex items-center space-x-6">
                <div class="text-center">
                    <p class="text-2xl font-bold counter" style="color: var(--text-primary)" data-target="{{ $stats['total_aircraft'] }}">0</p>
                    <p class="text-xs" style="color: var(--text-muted)">Aircraft Live</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-wimf-400 counter" data-target="{{ $stats['total_countries'] }}">0</p>
                    <p class="text-xs" style="color: var(--text-muted)">Countries</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-emerald-400" id="avg-alt">{{ number_format($stats['avg_altitude_m']) }}m</p>
                    <p class="text-xs" style="color: var(--text-muted)">Avg Altitude</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-amber-400" id="avg-speed">{{ number_format($stats['avg_speed_kmh']) }} km/h</p>
                    <p class="text-xs" style="color: var(--text-muted)">Avg Speed</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════ FILTERS ═══════════════ --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
        <div class="flex items-center space-x-3">
            <label class="text-sm font-medium" style="color: var(--text-muted)">Filter by Country:</label>
            <select id="country-filter" onchange="filterByCountry(this.value)"
                    class="wimf-input px-4 py-2 rounded-xl text-sm outline-none transition-all appearance-none cursor-pointer">
                <option value="all" {{ $selectedCountry === 'all' ? 'selected' : '' }}>🌍 All Countries</option>
                @foreach($countries as $countryName => $count)
                    <option value="{{ $countryName }}" {{ $selectedCountry === $countryName ? 'selected' : '' }}>
                        {{ getCountryFlag($countryName) }} {{ $countryName }} ({{ $count }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="flex items-center space-x-2 ml-auto">
            <span class="text-xs" style="color: var(--text-muted)" id="last-updated">Auto-refreshes every 15s</span>
            <button onclick="refreshFlights()" 
                    class="px-4 py-2 rounded-xl bg-wimf-600/20 border border-wimf-600/30 text-wimf-400 text-sm font-medium hover:bg-wimf-600/30 transition-all"
                    id="refresh-btn">
                ↻ Refresh Now
            </button>
        </div>
    </div>

    {{-- ═══════════════ FLIGHT CARDS GRID ═══════════════ --}}
    <div id="flights-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 stagger-children">
        @foreach($flights as $index => $flight)
            @include('flights._flight_card', ['flight' => $flight, 'index' => $index])
        @endforeach

        @if(count($flights) === 0)
            <div class="col-span-full py-20 text-center">
                <p class="text-lg" style="color: var(--text-muted)">No aircraft data available right now</p>
                <p class="text-sm mt-2" style="color: var(--text-secondary)">The OpenSky API might be rate-limited. Try again in a few minutes.</p>
            </div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script>
    // ── Animated Counters ──
    document.querySelectorAll('.counter').forEach(counter => {
        const target = parseInt(counter.dataset.target);
        const duration = 1500;
        const step = target / (duration / 16);
        let current = 0;
        const timer = setInterval(() => {
            current += step;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            counter.textContent = Math.floor(current).toLocaleString();
        }, 16);
    });

    // ── Country Filter ──
    function filterByCountry(country) {
        window.location.href = "{{ route('live.feed') }}?country=" + encodeURIComponent(country);
    }

    // ── Auto-Refresh via AJAX ──
    let refreshInterval;

    function refreshFlights() {
        const btn = document.getElementById('refresh-btn');
        btn.textContent = '⟳ Loading...';
        btn.disabled = true;

        const country = document.getElementById('country-filter').value;
        
        fetch(`{{ route('api.live.flights') }}?country=${encodeURIComponent(country)}`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            }
        })
        .then(r => r.json())
        .then(data => {
            const grid = document.getElementById('flights-grid');
            grid.innerHTML = '';

            data.flights.forEach((flight, index) => {
                const card = createFlightCard(flight, index);
                grid.appendChild(card);
            });

            // Update stats
            document.getElementById('avg-alt').textContent = data.stats.avg_altitude_m.toLocaleString() + 'm';
            document.getElementById('avg-speed').textContent = data.stats.avg_speed_kmh.toLocaleString() + ' km/h';

            document.getElementById('last-updated').textContent = 
                'Updated: ' + new Date().toLocaleTimeString();
            
            btn.textContent = '↻ Refresh Now';
            btn.disabled = false;
        })
        .catch(err => {
            console.error('Refresh failed:', err);
            btn.textContent = '↻ Refresh Now';
            btn.disabled = false;
        });
    }

    function createFlightCard(flight, index) {
        const card = document.createElement('a');
        card.href = `/flight/${flight.callsign.trim()}`;
        card.className = 'flight-card glass-card rounded-2xl p-5 hover:scale-[1.02] transition-all block';
        card.style.animationDelay = `${(index % 20) * 30}ms`;
        card.style.opacity = '0';
        card.style.animation = `fadeInUp 0.4s cubic-bezier(0.4, 0, 0.2, 1) ${(index % 20) * 30}ms forwards`;

        const altitudeColor = flight.altitude > 10000 ? 'text-emerald-400' : 
                             flight.altitude > 5000 ? 'text-amber-400' : 'text-rose-400';
        const groundBadge = flight.on_ground ? 
            '<span class="px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-400 text-xs font-medium">On Ground</span>' :
            '<span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 text-xs font-medium">In Flight</span>';

        const headingArrow = getHeadingArrow(flight.heading);
        const locationDesc = flight.location_desc || '';

        card.innerHTML = `
            <div class="flex items-start justify-between mb-3">
                <div>
                    <h3 class="font-mono font-bold text-lg tracking-wide" style="color: var(--text-primary)">${flight.callsign}</h3>
                    <p class="text-sm flex items-center gap-1" style="color: var(--text-muted)">
                        <span>${getCountryFlagJS(flight.origin_country)}</span>
                        <span>${flight.origin_country}</span>
                    </p>
                </div>
                <div class="heading-indicator text-2xl" style="transform: rotate(${flight.heading}deg)" title="Heading: ${flight.heading}°">
                    ✈
                </div>
            </div>
            ${locationDesc ? `<p class="text-xs mb-2 flex items-center gap-1" style="color:var(--text-secondary)"><span>📍</span><span>${locationDesc}</span></p>` : ''}
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <p class="text-xs" style="color: var(--text-muted)">Altitude</p>
                    <p class="font-mono font-semibold ${altitudeColor}">${flight.altitude.toLocaleString()}m</p>
                </div>
                <div>
                    <p class="text-xs" style="color: var(--text-muted)">Speed</p>
                    <p class="font-mono font-semibold text-wimf-400">${Math.round(flight.velocity * 3.6).toLocaleString()} km/h</p>
                </div>
                <div>
                    <p class="text-xs" style="color: var(--text-muted)">Heading</p>
                    <p class="font-mono font-semibold" style="color: var(--text-primary)">${flight.heading}° ${headingArrow}</p>
                </div>
                <div>
                    <p class="text-xs" style="color: var(--text-muted)">V/Rate</p>
                    <p class="font-mono font-semibold ${flight.vertical_rate > 0 ? 'text-emerald-400' : flight.vertical_rate < 0 ? 'text-rose-400' : ''}" ${flight.vertical_rate === 0 ? 'style="color: var(--text-muted)"' : ''}>${flight.vertical_rate > 0 ? '↑' : flight.vertical_rate < 0 ? '↓' : '—'} ${Math.abs(flight.vertical_rate)} m/s</p>
                </div>
            </div>
            <div class="mt-3 flex items-center justify-between">
                ${groundBadge}
                <span class="text-xs font-mono" style="color: var(--text-muted)">${flight.icao24}</span>
            </div>
        `;

        return card;
    }

    function getHeadingArrow(heading) {
        const directions = ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW'];
        const index = Math.round(heading / 45) % 8;
        return directions[index];
    }

    function getCountryFlagJS(country) {
        const flags = {
            'United States': '🇺🇸', 'United Kingdom': '🇬🇧', 'Germany': '🇩🇪', 'France': '🇫🇷',
            'China': '🇨🇳', 'Japan': '🇯🇵', 'India': '🇮🇳', 'Canada': '🇨🇦', 'Australia': '🇦🇺',
            'Brazil': '🇧🇷', 'Russia': '🇷🇺', 'South Korea': '🇰🇷', 'Italy': '🇮🇹', 'Spain': '🇪🇸',
            'Netherlands': '🇳🇱', 'Turkey': '🇹🇷', 'Singapore': '🇸🇬', 'UAE': '🇦🇪', 
            'Saudi Arabia': '🇸🇦', 'Mexico': '🇲🇽', 'Indonesia': '🇮🇩', 'Thailand': '🇹🇭',
            'Switzerland': '🇨🇭', 'Sweden': '🇸🇪', 'Norway': '🇳🇴', 'Denmark': '🇩🇰',
            'Ireland': '🇮🇪', 'Portugal': '🇵🇹', 'Poland': '🇵🇱', 'Austria': '🇦🇹',
            'Belgium': '🇧🇪', 'Finland': '🇫🇮', 'Czech Republic': '🇨🇿', 'Israel': '🇮🇱',
            'South Africa': '🇿🇦', 'Argentina': '🇦🇷', 'Chile': '🇨🇱', 'Colombia': '🇨🇴',
            'Malaysia': '🇲🇾', 'Philippines': '🇵🇭', 'Vietnam': '🇻🇳', 'New Zealand': '🇳🇿',
            'Taiwan': '🇹🇼', 'Hong Kong': '🇭🇰', 'Pakistan': '🇵🇰', 'Bangladesh': '🇧🇩',
            'Egypt': '🇪🇬', 'Qatar': '🇶🇦', 'Kuwait': '🇰🇼', 'Ethiopia': '🇪🇹',
            'Kenya': '🇰🇪', 'Nigeria': '🇳🇬', 'Morocco': '🇲🇦', 'Qatar': '🇶🇦',
        };
        return flags[country] || '🌍';
    }

    // Auto-refresh every 15 seconds
    refreshInterval = setInterval(refreshFlights, 15000);

    // Pause auto-refresh when page is not visible
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            clearInterval(refreshInterval);
        } else {
            refreshFlights();
            refreshInterval = setInterval(refreshFlights, 15000);
        }
    });
</script>
@endpush
