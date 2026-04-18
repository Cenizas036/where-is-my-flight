<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Where Is My Flight — Real-time flight tracking with community-powered gate info and AI delay predictions">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'Where Is My Flight') — Live Flight Tracker</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <!-- Tailwind CSS (CDN for dev, compile for prod) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    },
                    colors: {
                        wimf: {
                            50:  '#eef7ff',
                            100: '#d9edff',
                            200: '#bce0ff',
                            300: '#8eceff',
                            400: '#59b2ff',
                            500: '#3391ff',
                            600: '#1a6ff5',
                            700: '#1359e1',
                            800: '#1648b6',
                            900: '#183f8f',
                            950: '#142857',
                        },
                        ontime:   { DEFAULT: '#10b981', dark: '#059669' },
                        delayed:  { DEFAULT: '#f59e0b', dark: '#d97706' },
                        cancelled:{ DEFAULT: '#ef4444', dark: '#dc2626' },
                        boarding: { DEFAULT: '#8b5cf6', dark: '#7c3aed' },
                        landed:   { DEFAULT: '#06b6d4', dark: '#0891b2' },
                    },
                    animation: {
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'board-row':  'slideInRow 0.3s ease-out forwards',
                    },
                    keyframes: {
                        slideInRow: {
                            '0%':   { opacity: 0, transform: 'translateY(-8px)' },
                            '100%': { opacity: 1, transform: 'translateY(0)' },
                        }
                    }
                }
            }
        }
    </script>

    <!-- Custom Styles -->
    <link rel="stylesheet" href="/css/wimf.css">

    @stack('styles')
</head>
<body class="bg-gray-950 text-gray-100 font-sans antialiased min-h-screen">
    
    {{-- ═══════════════ NAVIGATION ═══════════════ --}}
    <nav class="sticky top-0 z-50 border-b border-gray-800/50 bg-gray-950/80 backdrop-blur-xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                
                {{-- Logo --}}
                <a href="{{ route('home') }}" class="flex items-center space-x-3 group">
                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-wimf-400 to-wimf-600 flex items-center justify-center shadow-lg shadow-wimf-500/20 group-hover:shadow-wimf-500/40 transition-shadow">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                    </div>
                    <span class="text-lg font-bold bg-gradient-to-r from-wimf-300 to-wimf-500 bg-clip-text text-transparent">
                        Where Is My Flight
                    </span>
                </a>

                {{-- Nav Links --}}
                <div class="hidden md:flex items-center space-x-1">
                    <a href="{{ route('flights.board') }}" 
                       class="px-4 py-2 rounded-lg text-sm font-medium text-gray-300 hover:text-white hover:bg-gray-800/60 transition-all">
                        Live Board
                    </a>
                    <a href="{{ route('flights.search') }}" 
                       class="px-4 py-2 rounded-lg text-sm font-medium text-gray-300 hover:text-white hover:bg-gray-800/60 transition-all">
                        Search
                    </a>
                    @auth
                        <a href="{{ route('flights.mine') }}" 
                           class="px-4 py-2 rounded-lg text-sm font-medium text-gray-300 hover:text-white hover:bg-gray-800/60 transition-all">
                            My Flights
                        </a>
                        <a href="{{ route('contributions.mine') }}" 
                           class="px-4 py-2 rounded-lg text-sm font-medium text-gray-300 hover:text-white hover:bg-gray-800/60 transition-all">
                            Contributions
                        </a>
                    @endauth
                </div>

                {{-- Auth / User --}}
                <div class="flex items-center space-x-3">
                    @auth
                        <a href="{{ route('profile') }}" class="flex items-center space-x-2 px-3 py-1.5 rounded-lg hover:bg-gray-800/60 transition-all">
                            <div class="w-7 h-7 rounded-full bg-gradient-to-br from-wimf-400 to-wimf-600 flex items-center justify-center text-xs font-bold text-white">
                                {{ strtoupper(substr(auth()->user()->display_name, 0, 1)) }}
                            </div>
                            <span class="text-sm text-gray-300">{{ auth()->user()->display_name }}</span>
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="text-sm text-gray-500 hover:text-gray-300 transition-colors">Logout</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="px-4 py-2 text-sm font-medium text-gray-300 hover:text-white transition-colors">
                            Sign In
                        </a>
                        <a href="{{ route('register') }}" 
                           class="px-4 py-2 text-sm font-semibold rounded-lg bg-wimf-600 text-white hover:bg-wimf-500 shadow-lg shadow-wimf-600/20 transition-all">
                            Join
                        </a>
                    @endelse
                </div>
            </div>
        </div>
    </nav>

    {{-- ═══════════════ FLASH MESSAGES ═══════════════ --}}
    @if(session('success'))
        <div class="max-w-7xl mx-auto px-4 mt-4">
            <div class="rounded-lg bg-ontime/10 border border-ontime/20 text-ontime px-4 py-3 text-sm">
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="max-w-7xl mx-auto px-4 mt-4">
            <div class="rounded-lg bg-cancelled/10 border border-cancelled/20 text-cancelled px-4 py-3 text-sm">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ═══════════════ MAIN CONTENT ═══════════════ --}}
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @yield('content')
    </main>

    {{-- ═══════════════ FOOTER ═══════════════ --}}
    <footer class="border-t border-gray-800/50 mt-16">
        <div class="max-w-7xl mx-auto px-4 py-8">
            <div class="flex flex-col md:flex-row items-center justify-between space-y-4 md:space-y-0">
                <div class="flex items-center space-x-2 text-gray-500 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                    <span>Where Is My Flight — Community-powered flight tracking</span>
                </div>
                <div class="flex items-center space-x-4 text-gray-600 text-xs">
                    <span>Data: AviationStack / FlightAware</span>
                    <span>•</span>
                    <span>Weather: OpenWeatherMap</span>
                    <span>•</span>
                    <span>Predictions: Apache Spark</span>
                </div>
            </div>
        </div>
    </footer>

    {{-- ═══════════════ SCALA.JS ═══════════════ --}}
    {{-- Compiled Scala.js reactive components mount here --}}
    <script src="/js/scalajs/wimf-frontend-opt.js" defer></script>

    {{-- Pass server config to Scala.js --}}
    <script>
        window.WIMF_CONFIG = {
            wsEndpoint: @json(config('wimf.websocket_url')),
            csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            apiBase: '/api',
            boardRefreshRate: {{ config('wimf.board_refresh_rate', 30) }},
            isAuthenticated: @json(auth()->check()),
        };
    </script>

    @stack('scripts')
</body>
</html>
