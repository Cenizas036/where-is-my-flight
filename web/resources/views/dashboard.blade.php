@extends('layouts.app')
@section('title', 'Where Is My Flight — Real-Time Global Flight Tracking')

@section('content')
<div class="space-y-14">

    {{-- ═══════════════ HERO SECTION ═══════════════ --}}
    <div class="text-center space-y-8 pt-12 pb-8 relative">
        {{-- Decorative glow --}}
        <div class="absolute inset-0 flex items-center justify-center pointer-events-none" aria-hidden="true">
            <div class="w-[500px] h-[500px] rounded-full bg-wimf-500/5 blur-3xl"></div>
        </div>

        <div class="relative">
            <h1 class="text-5xl md:text-7xl font-black tracking-tight fade-in-up">
                <span class="gradient-text">
                    Where Is My Flight?
                </span>
            </h1>
            <p class="text-lg md:text-xl max-w-2xl mx-auto leading-relaxed mt-6 fade-in-up" style="color: var(--text-secondary); animation-delay: 0.1s">
                Real-time flight tracking with 
                <span class="font-semibold text-wimf-400">live aircraft data</span>
                from around the world.
                <br>
                Track <span class="font-semibold text-emerald-400">thousands of planes</span> in the sky right now.
            </p>
        </div>

        {{-- CTA Buttons --}}
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 fade-in-up" style="animation-delay: 0.2s">
            @auth
                <a href="{{ route('live.feed') }}" 
                   class="px-8 py-4 rounded-2xl bg-gradient-to-r from-wimf-500 to-wimf-700 text-white font-bold text-lg shadow-2xl shadow-wimf-600/30 hover:shadow-wimf-500/50 transition-all duration-300 hover:scale-105 flex items-center space-x-2">
                    <span class="w-3 h-3 rounded-full bg-green-400 animate-pulse"></span>
                    <span>Open Live Feed</span>
                </a>
                <a href="{{ route('flights.board') }}" 
                   class="px-8 py-4 rounded-2xl glass-card text-lg font-semibold hover:scale-105 transition-all duration-300" 
                   style="color: var(--text-primary)">
                    Flight Board →
                </a>
            @else
                <a href="{{ route('register') }}" 
                   class="px-8 py-4 rounded-2xl bg-gradient-to-r from-wimf-500 to-wimf-700 text-white font-bold text-lg shadow-2xl shadow-wimf-600/30 hover:shadow-wimf-500/50 transition-all duration-300 hover:scale-105">
                    ✈ Join Free — Start Tracking
                </a>
                <a href="{{ route('login') }}" 
                   class="px-8 py-4 rounded-2xl glass-card text-lg font-semibold hover:scale-105 transition-all duration-300" 
                   style="color: var(--text-primary)">
                    Sign In →
                </a>
            @endauth
        </div>

        {{-- Search Bar --}}
        @auth
        <form action="{{ route('flights.search') }}" method="GET" class="max-w-xl mx-auto fade-in-up" style="animation-delay: 0.3s">
            <div class="flex items-center rounded-2xl border focus-within:border-wimf-500 focus-within:ring-2 focus-within:ring-wimf-500/20 transition-all overflow-hidden shadow-2xl" 
                 style="background: var(--bg-card); border-color: var(--border-card);">
                <input type="text" name="q" placeholder="Search flight number, airport, or route..." 
                       class="flex-1 px-6 py-4 bg-transparent placeholder-gray-500 outline-none text-lg" style="color: var(--text-primary)">
                <button type="submit" class="px-8 py-4 bg-wimf-600 text-white font-semibold hover:bg-wimf-500 transition-colors">
                    Search
                </button>
            </div>
        </form>
        @endauth
    </div>

    {{-- ═══════════════ QUICK AIRPORT SELECT ═══════════════ --}}
    <div class="fade-in-up" style="animation-delay: 0.4s">
        <h2 class="text-lg font-semibold mb-4" style="color: var(--text-secondary)">Quick — Pick an Airport</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            @foreach($popularAirports as $airport)
                @auth
                <a href="{{ route('flights.board', ['airport' => $airport['iata']]) }}"
                @else
                <a href="{{ route('login') }}"
                @endauth
                   class="group glass-card rounded-xl p-5 hover:scale-105 transition-all duration-300">
                    <p class="font-mono font-bold text-2xl group-hover:text-wimf-400 transition-colors" style="color: var(--text-primary)">
                        {{ $airport['iata'] }}
                    </p>
                    <p class="text-xs mt-1" style="color: var(--text-muted)">{{ $airport['city'] }}</p>
                </a>
            @endforeach
        </div>
    </div>

    {{-- ═══════════════ LIVE STATS ═══════════════ --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 fade-in-up" style="animation-delay: 0.5s">
        <div class="glass-card rounded-2xl p-6 pulse-glow">
            <p class="text-3xl font-bold text-white counter" data-target="{{ $stats['active_flights'] }}">0</p>
            <p class="text-sm mt-1" style="color: var(--text-muted)">Active Flights Today</p>
        </div>
        <div class="glass-card rounded-2xl p-6" style="border-color: rgba(139, 92, 246, 0.2);">
            <p class="text-3xl font-bold text-purple-400 counter" data-target="{{ $stats['community_updates'] }}">0</p>
            <p class="text-sm mt-1" style="color: var(--text-muted)">Community Updates Today</p>
        </div>
        <div class="glass-card rounded-2xl p-6" style="border-color: rgba(16, 185, 129, 0.2);">
            <p class="text-3xl font-bold text-emerald-400 counter" data-target="{{ $stats['tracked_flights'] }}">0</p>
            <p class="text-sm mt-1" style="color: var(--text-muted)">Flights Being Tracked</p>
        </div>
    </div>

    {{-- ═══════════════ WHY JOIN (for guests) ═══════════════ --}}
    @guest
    <div class="glass-card rounded-3xl p-10 text-center fade-in-up" style="animation-delay: 0.6s">
        <h2 class="text-3xl font-bold mb-6" style="color: var(--text-primary)">Why Join <span class="gradient-text">Where Is My Flight</span>?</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-8">
            <div class="space-y-3">
                <div class="text-4xl">🌍</div>
                <h3 class="font-bold text-lg" style="color: var(--text-primary)">Global Real-Time Data</h3>
                <p class="text-sm" style="color: var(--text-secondary)">Track thousands of aircraft worldwide with live data from every country.</p>
            </div>
            <div class="space-y-3">
                <div class="text-4xl">🤖</div>
                <h3 class="font-bold text-lg" style="color: var(--text-primary)">AI Delay Predictions</h3>
                <p class="text-sm" style="color: var(--text-secondary)">Machine learning models predict delays before they're announced.</p>
            </div>
            <div class="space-y-3">
                <div class="text-4xl">👥</div>
                <h3 class="font-bold text-lg" style="color: var(--text-primary)">Community Gate Info</h3>
                <p class="text-sm" style="color: var(--text-secondary)">Crowdsourced gate updates from fellow travelers at the airport.</p>
            </div>
        </div>
        <a href="{{ route('register') }}" 
           class="inline-block mt-10 px-10 py-4 rounded-2xl bg-gradient-to-r from-wimf-500 to-wimf-700 text-white font-bold text-lg shadow-2xl shadow-wimf-600/30 hover:shadow-wimf-500/50 transition-all duration-300 hover:scale-105">
            Sign Up Free →
        </a>
    </div>
    @endguest

    {{-- ═══════════════ DELAYED FLIGHTS ═══════════════ --}}
    @if($delayedFlights->count() > 0)
    <div class="fade-in-up" style="animation-delay: 0.7s">
        <h2 class="text-lg font-semibold text-amber-400 mb-4 flex items-center space-x-2">
            <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-pulse"></span>
            <span>Currently Delayed</span>
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($delayedFlights as $flight)
                @auth
                <a href="{{ route('flight.profile', ['callsign' => $flight->flight_number]) }}"
                @else
                <a href="{{ route('login') }}"
                @endauth
                   class="glass-card rounded-xl p-5 group" style="border-color: rgba(245, 158, 11, 0.15);">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-mono font-bold group-hover:text-amber-400 transition-colors" style="color: var(--text-primary)">
                            {{ $flight->flight_number }}
                        </span>
                        <span class="text-amber-400 text-sm font-semibold">+{{ $flight->delay_minutes }}min</span>
                    </div>
                    <p class="text-sm" style="color: var(--text-muted)">
                        {{ $flight->departureAirport->iata_code ?? '?' }} → {{ $flight->arrivalAirport->iata_code ?? '?' }}
                    </p>
                    @if($flight->delay_reason)
                        <p class="text-xs mt-1 capitalize" style="color: var(--text-muted)">{{ $flight->delay_reason }}</p>
                    @endif
                </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ═══════════════ RECENT COMMUNITY ACTIVITY ═══════════════ --}}
    @if($recentContributions->count() > 0)
    <div class="fade-in-up" style="animation-delay: 0.8s">
        <h2 class="text-lg font-semibold mb-4" style="color: var(--text-secondary)">Recent Community Updates</h2>
        <div class="space-y-2">
            @foreach($recentContributions as $contrib)
                <div class="flex items-center space-x-4 glass-card rounded-xl px-5 py-3">
                    <div class="w-8 h-8 rounded-full bg-wimf-600/20 flex items-center justify-center text-xs font-bold text-wimf-400">
                        {{ strtoupper(substr($contrib->user->display_name ?? '?', 0, 1)) }}
                    </div>
                    <div class="flex-1 text-sm">
                        <span style="color: var(--text-secondary)">{{ $contrib->user->display_name ?? 'User' }}</span>
                        <span style="color: var(--text-muted)"> updated gate to </span>
                        <span class="font-mono font-bold text-wimf-400">{{ $contrib->gate_number }}</span>
                        <span style="color: var(--text-muted)"> for </span>
                        <span class="font-mono" style="color: var(--text-primary)">{{ $contrib->flight->flight_number ?? 'Unknown' }}</span>
                    </div>
                    <span class="text-xs" style="color: var(--text-muted)">{{ $contrib->created_at->diffForHumans() }}</span>
                </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
    // Animated counters
    document.querySelectorAll('.counter').forEach(counter => {
        const target = parseInt(counter.dataset.target) || 0;
        if (target === 0) { counter.textContent = '0'; return; }
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
</script>
@endpush
