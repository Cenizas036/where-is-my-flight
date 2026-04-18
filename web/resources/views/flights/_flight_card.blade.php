{{-- Single flight card for live feed --}}
@php
    $altitudeColor = $flight['altitude'] > 10000 ? 'text-emerald-400' : ($flight['altitude'] > 5000 ? 'text-amber-400' : 'text-rose-400');
    $vrateColor = $flight['vertical_rate'] > 0 ? 'text-emerald-400' : ($flight['vertical_rate'] < 0 ? 'text-rose-400' : 'text-gray-400');
    $vrateIcon = $flight['vertical_rate'] > 0 ? '↑' : ($flight['vertical_rate'] < 0 ? '↓' : '—');
    $headingDirs = ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW'];
    $headingDir = $headingDirs[round($flight['heading'] / 45) % 8];
    $locationDesc = $flight['location_desc'] ?? getLocationDescription($flight['latitude'], $flight['longitude'], $flight['on_ground']);
@endphp

<a href="{{ route('flight.profile', ['callsign' => trim($flight['callsign'])]) }}"
   class="flight-card glass-card rounded-2xl p-5 hover:scale-[1.02] transition-all duration-300 block"
   style="animation-delay: {{ ($index % 20) * 30 }}ms">
    
    {{-- Header: Callsign + Heading --}}
    <div class="flex items-start justify-between mb-3">
        <div>
            <h3 class="font-mono font-bold text-lg tracking-wide" style="color: var(--text-primary)">{{ $flight['callsign'] }}</h3>
            <p class="text-sm flex items-center gap-1" style="color: var(--text-muted)">
                <span>{{ getCountryFlag($flight['origin_country']) }}</span>
                <span>{{ $flight['origin_country'] }}</span>
            </p>
        </div>
        <div class="heading-indicator text-2xl" 
             style="transform: rotate({{ $flight['heading'] }}deg)" 
             title="Heading: {{ $flight['heading'] }}°">
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
            <p class="font-mono font-semibold {{ $altitudeColor }}">{{ number_format($flight['altitude']) }}m</p>
        </div>
        <div>
            <p class="text-xs" style="color: var(--text-muted)">Speed</p>
            <p class="font-mono font-semibold text-wimf-400">{{ number_format(round($flight['velocity'] * 3.6)) }} km/h</p>
        </div>
        <div>
            <p class="text-xs" style="color: var(--text-muted)">Heading</p>
            <p class="font-mono font-semibold" style="color: var(--text-primary)">{{ $flight['heading'] }}° {{ $headingDir }}</p>
        </div>
        <div>
            <p class="text-xs" style="color: var(--text-muted)">V/Rate</p>
            <p class="font-mono font-semibold {{ $vrateColor }}">{{ $vrateIcon }} {{ abs($flight['vertical_rate']) }} m/s</p>
        </div>
    </div>

    {{-- Footer --}}
    <div class="mt-3 flex items-center justify-between">
        @if($flight['on_ground'])
            <span class="px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-400 text-xs font-medium">On Ground</span>
        @else
            <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 text-xs font-medium">In Flight</span>
        @endif
        <span class="text-xs font-mono" style="color: var(--text-muted)">{{ $flight['icao24'] }}</span>
    </div>
</a>

