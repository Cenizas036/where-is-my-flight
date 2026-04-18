<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Where Is My Flight — Real-time flight tracking with community-powered gate info and AI delay predictions">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'Where Is My Flight') — Live Flight Tracker</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Tailwind & SCSS via Vite -->
    @vite(['resources/css/tailwind.css', 'resources/css/app.scss', 'resources/js/app.js'])

    <!-- Custom Styles -->
    <link rel="stylesheet" href="/css/wimf.css">

    @stack('styles')
</head>
<body class="font-sans antialiased min-h-screen transition-colors duration-500">
    
    {{-- ═══════════════ ANIMATED BACKGROUND ═══════════════ --}}
    <div class="aurora-bg" aria-hidden="true">
        <div class="aurora-gradient aurora-1"></div>
        <div class="aurora-gradient aurora-2"></div>
        <div class="aurora-gradient aurora-3"></div>
    </div>

    {{-- Floating airplane particles — left-to-right and right-to-left --}}
    <div class="airplane-particles" aria-hidden="true">
        <span class="particle" style="--delay: 0s; --duration: 22s; --top: 8%;">✈</span>
        <span class="particle" style="--delay: 3s; --duration: 28s; --top: 22%;">✈</span>
        <span class="particle" style="--delay: 6s; --duration: 18s; --top: 38%;">✈</span>
        <span class="particle" style="--delay: 10s; --duration: 25s; --top: 55%;">✈</span>
        <span class="particle" style="--delay: 14s; --duration: 32s; --top: 72%;">✈</span>
        <span class="particle" style="--delay: 1s; --duration: 20s; --top: 85%;">✈</span>
        <span class="particle" style="--delay: 8s; --duration: 30s; --top: 15%;">✈</span>
        <span class="particle reverse" style="--delay: 2s; --duration: 26s; --top: 18%;">✈</span>
        <span class="particle reverse" style="--delay: 7s; --duration: 22s; --top: 42%;">✈</span>
        <span class="particle reverse" style="--delay: 11s; --duration: 28s; --top: 62%;">✈</span>
        <span class="particle reverse" style="--delay: 15s; --duration: 24s; --top: 78%;">✈</span>
        <span class="particle reverse" style="--delay: 5s; --duration: 30s; --top: 32%;">✈</span>
    </div>

    {{-- ═══════════════ NAVIGATION — Enhanced ═══════════════ --}}
    <nav class="sticky top-0 z-50 nav-glass">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                
                {{-- Logo --}}
                <a href="{{ route('home') }}" class="flex items-center space-x-3 group">
                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-wimf-400 to-wimf-600 flex items-center justify-center shadow-lg shadow-wimf-500/20 group-hover:shadow-wimf-500/40 transition-all duration-300 group-hover:scale-110 pulse-glow">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z"/>
                        </svg>
                    </div>
                    <span class="text-lg font-bold gradient-text hidden sm:inline">
                        Where Is My Flight
                    </span>
                </a>

                {{-- Nav Links --}}
                <div class="hidden lg:flex items-center space-x-1">
                    @auth
                        <a href="{{ route('live.feed') }}" 
                           class="nav-link {{ request()->routeIs('live.feed') ? 'nav-link-active' : '' }}">
                            <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse mr-1.5 inline-block"></span>
                            Live Feed
                        </a>
                        <a href="{{ route('flights.board') }}" 
                           class="nav-link {{ request()->routeIs('flights.board') ? 'nav-link-active' : '' }}">
                            🛫 Board
                        </a>
                        <a href="{{ route('flights.search') }}" 
                           class="nav-link {{ request()->routeIs('flights.search') ? 'nav-link-active' : '' }}">
                            🔍 Search
                        </a>
                        <a href="{{ route('user.dashboard') }}" 
                           class="nav-link {{ request()->routeIs('user.dashboard') ? 'nav-link-active' : '' }}">
                            📍 Dashboard
                        </a>
                        <a href="{{ route('flights.booking') }}" 
                           class="nav-link {{ request()->routeIs('flights.booking') ? 'nav-link-active' : '' }}">
                            🎫 Book
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="nav-link">🛫 Board</a>
                        <a href="{{ route('login') }}" class="nav-link">🔍 Search</a>
                        <a href="{{ route('login') }}" class="nav-link">🎫 Book</a>
                    @endauth
                </div>

                {{-- Right Side --}}
                <div class="flex items-center space-x-2">
                    
                    {{-- Theme Toggle --}}
                    <button id="theme-toggle" onclick="toggleTheme()" 
                            class="w-10 h-10 rounded-xl flex items-center justify-center transition-all duration-300 hover:scale-110 theme-toggle-btn"
                            title="Toggle light/dark mode">
                        <svg id="theme-icon-sun" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <svg id="theme-icon-moon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                    </button>

                    @auth
                        {{-- User Avatar --}}
                        <a href="{{ route('user.dashboard') }}" class="flex items-center space-x-2 px-3 py-1.5 rounded-xl transition-all hover:scale-105"
                           style="background: var(--bg-card); border: 1px solid var(--border-card)">
                            <div class="w-7 h-7 rounded-full bg-gradient-to-br from-wimf-400 to-wimf-600 flex items-center justify-center text-xs font-bold text-white">
                                {{ strtoupper(substr(auth()->user()->display_name, 0, 1)) }}
                            </div>
                            <span class="text-sm font-medium hidden sm:inline" style="color: var(--text-primary)">{{ auth()->user()->display_name }}</span>
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="text-xs px-2 py-1 rounded-lg hover:bg-rose-500/10 hover:text-rose-400 transition-all" style="color: var(--text-muted)">Logout</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="px-4 py-2 text-sm font-medium nav-text hover:text-white transition-colors">
                            Sign In
                        </a>
                        <a href="{{ route('register') }}" 
                           class="px-5 py-2 text-sm font-semibold rounded-xl bg-gradient-to-r from-wimf-500 to-wimf-700 text-white hover:from-wimf-400 hover:to-wimf-600 shadow-lg shadow-wimf-600/20 hover:shadow-wimf-500/40 transition-all duration-300 hover:scale-105">
                            Join Free
                        </a>
                    @endauth

                    {{-- Mobile menu toggle --}}
                    <button onclick="document.getElementById('mobile-menu').classList.toggle('hidden')" class="lg:hidden w-10 h-10 rounded-xl flex items-center justify-center theme-toggle-btn">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Mobile Menu --}}
            <div id="mobile-menu" class="hidden lg:hidden pb-4 space-y-1">
                @auth
                    <a href="{{ route('live.feed') }}" class="block px-4 py-3 rounded-xl nav-link">● Live Feed</a>
                    <a href="{{ route('flights.board') }}" class="block px-4 py-3 rounded-xl nav-link">🛫 Board</a>
                    <a href="{{ route('flights.search') }}" class="block px-4 py-3 rounded-xl nav-link">🔍 Search</a>
                    <a href="{{ route('user.dashboard') }}" class="block px-4 py-3 rounded-xl nav-link">📍 Dashboard</a>
                    <a href="{{ route('flights.booking') }}" class="block px-4 py-3 rounded-xl nav-link">🎫 Book</a>
                    <a href="{{ route('flights.mine') }}" class="block px-4 py-3 rounded-xl nav-link">⭐ My Flights</a>
                @else
                    <a href="{{ route('login') }}" class="block px-4 py-3 rounded-xl nav-link">Sign In</a>
                    <a href="{{ route('register') }}" class="block px-4 py-3 rounded-xl nav-link">Register</a>
                @endauth
            </div>
        </div>
    </nav>

    {{-- ═══════════════ FLASH MESSAGES ═══════════════ --}}
    @if(session('success'))
        <div class="max-w-7xl mx-auto px-4 mt-4">
            <div class="rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 px-5 py-3 text-sm backdrop-blur-sm fade-in-up">
                ✓ {{ session('success') }}
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="max-w-7xl mx-auto px-4 mt-4">
            <div class="rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 px-5 py-3 text-sm backdrop-blur-sm fade-in-up">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ═══════════════ MAIN CONTENT ═══════════════ --}}
    <main class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @yield('content')
    </main>

    {{-- ═══════════════ FOOTER ═══════════════ --}}
    <footer class="relative z-10 border-t footer-border mt-16">
        <div class="max-w-7xl mx-auto px-4 py-8">
            <div class="flex flex-col md:flex-row items-center justify-between space-y-4 md:space-y-0">
                <div class="flex items-center space-x-2 footer-text text-sm">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z"/>
                    </svg>
                    <span>Where Is My Flight — Real-time global flight tracking</span>
                </div>
                <div class="flex items-center space-x-4 footer-text-dim text-xs">
                    <span>Data: OpenSky Network</span>
                    <span>•</span>
                    <span>Predictions: Apache Spark</span>
                </div>
            </div>
        </div>
    </footer>

    {{-- ═══════════════ THEME SCRIPT ═══════════════ --}}
    <script>
        // Load saved theme or default to dark
        (function() {
            const saved = localStorage.getItem('wimf-theme') || 'dark';
            document.documentElement.setAttribute('data-theme', saved);
            updateThemeIcons(saved);
        })();

        function toggleTheme() {
            const html = document.documentElement;
            const current = html.getAttribute('data-theme');
            const next = current === 'dark' ? 'light' : 'dark';
            
            html.setAttribute('data-theme', next);
            localStorage.setItem('wimf-theme', next);
            updateThemeIcons(next);
            
            // Add a nice transition pulse
            document.body.style.transition = 'background-color 0.5s ease, color 0.5s ease';
        }

        function updateThemeIcons(theme) {
            const sun = document.getElementById('theme-icon-sun');
            const moon = document.getElementById('theme-icon-moon');
            if (sun && moon) {
                if (theme === 'light') {
                    sun.classList.remove('hidden');
                    moon.classList.add('hidden');
                } else {
                    sun.classList.add('hidden');
                    moon.classList.remove('hidden');
                }
            }
        }
    </script>

    {{-- Pass server config to JS --}}
    <script>
        window.WIMF_CONFIG = {
            wsEndpoint: @json(config('wimf.websocket_url')),
            csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            apiBase: '/api',
            boardRefreshRate: {{ config('wimf.board_refresh_rate', 30) }},
            isAuthenticated: @json(auth()->check()),
        };
    </script>

    {{-- GSAP Animation Library (global) --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js" defer></script>

    @stack('scripts')
</body>
</html>
