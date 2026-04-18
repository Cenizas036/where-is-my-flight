@extends('layouts.app')
@section('title', $flight->flight_number . ' — Flight Details')

@section('content')
<div class="space-y-8">

    {{-- ═══════════════ FLIGHT HEADER ═══════════════ --}}
    <div class="glass-card rounded-2xl p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">

            {{-- Flight Identity --}}
            <div class="flex items-center space-x-5">
                @if($flight->airline && $flight->airline->logo_url)
                    <img src="{{ $flight->airline->logo_url }}" alt="{{ $flight->airline->name }}"
                         class="w-12 h-12 rounded-lg object-contain bg-white/10 p-1">
                @else
                    <div class="w-12 h-12 rounded-lg bg-wimf-600/20 flex items-center justify-center">
                            <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z"/>
                            </svg>
                    </div>
                @endif
                <div>
                    <h1 class="text-3xl font-bold text-white font-mono">{{ $flight->flight_number }}</h1>
                    <p class="text-gray-400 text-sm">{{ $flight->airline->name ?? 'Unknown Airline' }}</p>
                </div>
            </div>

            {{-- Status + Actions --}}
            <div class="flex items-center space-x-4">
                @php
                    $statusColors = [
                        'scheduled'  => 'text-gray-400 bg-gray-800 border-gray-700',
                        'boarding'   => 'text-boarding bg-boarding/10 border-boarding/30',
                        'departed'   => 'text-ontime bg-ontime/10 border-ontime/30',
                        'in_air'     => 'text-wimf-400 bg-wimf-600/10 border-wimf-500/30',
                        'landed'     => 'text-landed bg-landed/10 border-landed/30',
                        'arrived'    => 'text-ontime bg-ontime/10 border-ontime/30',
                        'delayed'    => 'text-delayed bg-delayed/10 border-delayed/30',
                        'cancelled'  => 'text-cancelled bg-cancelled/10 border-cancelled/30',
                    ];
                    $colorClass = $statusColors[$flight->status] ?? $statusColors['scheduled'];
                @endphp
                <span class="inline-flex items-center px-5 py-2 rounded-xl text-sm font-bold border {{ $colorClass }}">
                    @if($flight->status === 'boarding' || $flight->status === 'in_air')
                        <span class="w-2 h-2 rounded-full bg-current animate-pulse mr-2"></span>
                    @endif
                    {{ ucfirst(str_replace('_', ' ', $flight->status)) }}
                </span>

                @if($flight->delay_minutes > 0)
                    <span class="px-3 py-2 rounded-xl bg-delayed/10 text-delayed border border-delayed/20 text-sm font-bold delay-warning">
                        +{{ $flight->delay_minutes }}min
                    </span>
                @endif

                {{-- Watch/Unwatch --}}
                @auth
                    @if($isWatching)
                        <form method="POST" action="{{ route('flights.unwatch', $flight) }}">
                            @csrf @method('DELETE')
                            <button class="px-4 py-2 rounded-xl bg-gray-800 text-gray-400 hover:text-white hover:bg-gray-700 text-sm font-medium transition-all border border-gray-700">
                                ✓ Tracking
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('flights.watch', $flight) }}">
                            @csrf
                            <button class="px-4 py-2 rounded-xl bg-wimf-600 text-white hover:bg-wimf-500 text-sm font-semibold transition-all shadow-lg shadow-wimf-600/20">
                                Track Flight
                            </button>
                        </form>
                    @endif
                @endauth
            </div>
        </div>
    </div>

    {{-- ═══════════════ ROUTE DISPLAY ═══════════════ --}}
    <div class="glass-card rounded-2xl p-6">
        <div class="grid grid-cols-5 items-center gap-4">
            {{-- Departure --}}
            <div class="col-span-2 text-center">
                <p class="text-4xl font-bold font-mono text-white">{{ $flight->departureAirport->iata_code }}</p>
                <p class="text-sm text-gray-400 mt-1">{{ $flight->departureAirport->name }}</p>
                <p class="text-sm text-gray-500">{{ $flight->departureAirport->city }}, {{ $flight->departureAirport->country }}</p>

                <div class="mt-4 space-y-1">
                    <p class="text-xs text-gray-500 uppercase tracking-wider">Scheduled</p>
                    <p class="text-xl font-mono text-gray-200">
                        {{ \Carbon\Carbon::parse($flight->scheduled_departure)->format('H:i') }}
                    </p>
                    @if($flight->estimated_departure && $flight->estimated_departure !== $flight->scheduled_departure)
                        <p class="text-sm text-delayed font-mono">
                            Est: {{ \Carbon\Carbon::parse($flight->estimated_departure)->format('H:i') }}
                        </p>
                    @endif
                </div>
            </div>

            {{-- Route Line --}}
            <div class="col-span-1 flex flex-col items-center space-y-2">
                <div class="w-full h-px bg-gradient-to-r from-transparent via-gray-600 to-transparent"></div>
                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z"/>
                    </svg>
                <div class="w-full h-px bg-gradient-to-r from-transparent via-gray-600 to-transparent"></div>
                @if($flight->aircraft_type)
                    <p class="text-xs text-gray-600">{{ $flight->aircraft_type }}</p>
                @endif
            </div>

            {{-- Arrival --}}
            <div class="col-span-2 text-center">
                <p class="text-4xl font-bold font-mono text-white">{{ $flight->arrivalAirport->iata_code }}</p>
                <p class="text-sm text-gray-400 mt-1">{{ $flight->arrivalAirport->name }}</p>
                <p class="text-sm text-gray-500">{{ $flight->arrivalAirport->city }}, {{ $flight->arrivalAirport->country }}</p>

                <div class="mt-4 space-y-1">
                    <p class="text-xs text-gray-500 uppercase tracking-wider">Scheduled</p>
                    <p class="text-xl font-mono text-gray-200">
                        {{ \Carbon\Carbon::parse($flight->scheduled_arrival)->format('H:i') }}
                    </p>
                    @if($flight->estimated_arrival && $flight->estimated_arrival !== $flight->scheduled_arrival)
                        <p class="text-sm text-delayed font-mono">
                            Est: {{ \Carbon\Carbon::parse($flight->estimated_arrival)->format('H:i') }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ═══════════════ PREDICTION CARD ═══════════════ --}}
        <div class="glass-card rounded-2xl p-6">
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Delay Prediction</h2>

            {{-- Scala.js PredictionCard mounts here --}}
            <div class="prediction-mount" data-flight-id="{{ $flight->id }}">
                @if($prediction)
                    @php $prob = ($prediction['delay_probability'] ?? 0) * 100; @endphp
                    <div class="space-y-4">
                        <div class="text-center">
                            <p class="text-5xl font-bold font-mono {{ $prob > 60 ? 'text-cancelled' : ($prob > 30 ? 'text-delayed' : 'text-ontime') }}">
                                {{ round($prob) }}%
                            </p>
                            <p class="text-xs text-gray-500 mt-1">Delay Probability</p>
                        </div>

                        <div class="prediction-bar">
                            <div class="prediction-fill {{ $prob > 60 ? 'prediction-high' : ($prob > 30 ? 'prediction-mid' : 'prediction-low') }}"
                                 style="width: {{ $prob }}%; --prediction-width: {{ $prob }}%"></div>
                        </div>

                        @if(isset($prediction['estimated_delay_min']) && $prediction['estimated_delay_min'] > 0)
                            <p class="text-center text-lg font-mono text-gray-300">
                                ~{{ $prediction['estimated_delay_min'] }}min estimated delay
                            </p>
                        @endif

                        @if(isset($prediction['primary_cause']) && $prediction['primary_cause'] !== 'none')
                            <div class="flex items-center justify-center space-x-2">
                                <span class="text-sm text-gray-500">Primary cause:</span>
                                <span class="px-3 py-1 rounded-full bg-gray-800 text-gray-300 text-sm capitalize">
                                    {{ str_replace('_', ' ', $prediction['primary_cause']) }}
                                </span>
                            </div>
                        @endif
                    </div>
                @else
                    <p class="text-center text-gray-600 py-8">Prediction loading...</p>
                @endif
            </div>
        </div>

        {{-- ═══════════════ GATE INFO ═══════════════ --}}
        <div class="glass-card rounded-2xl p-6">
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Gate & Terminal</h2>

            <div class="space-y-4">
                {{-- Official Gate --}}
                <div class="text-center">
                    @if($flight->departure_gate)
                        <p class="text-5xl font-bold font-mono text-white">{{ $flight->departure_gate }}</p>
                        <p class="text-xs text-gray-500 mt-1">Official Gate</p>
                    @else
                        <p class="text-2xl font-mono text-gray-600">TBA</p>
                        <p class="text-xs text-gray-500 mt-1">Gate not yet assigned</p>
                    @endif
                </div>

                @if($flight->departure_terminal)
                    <div class="text-center">
                        <span class="px-4 py-1.5 rounded-full bg-gray-800 text-gray-300 text-sm font-mono">
                            Terminal {{ $flight->departure_terminal }}
                        </span>
                    </div>
                @endif

                {{-- Community Gate Contributions --}}
                @if($gateContributions->count() > 0)
                    <div class="border-t border-gray-800 pt-4 mt-4">
                        <p class="text-xs text-gray-500 uppercase tracking-wider mb-3">Community Reports</p>
                        @foreach($gateContributions->take(3) as $contrib)
                            <div class="flex items-center justify-between py-2">
                                <span class="gate-badge {{ $contrib->is_verified ? 'gate-badge-community' : '' }}">
                                    {{ $contrib->gate_number }}
                                </span>
                                <span class="text-xs text-gray-500">
                                    {{ $contrib->user->display_name }} · {{ round($contrib->confidence_score * 100) }}%
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Gate Edit Widget mount point --}}
            @auth
                <div id="gate-edit-root" data-flight-id="{{ $flight->id }}" class="mt-4"></div>
            @endauth
        </div>

        {{-- ═══════════════ FLIGHT DETAILS ═══════════════ --}}
        <div class="glass-card rounded-2xl p-6">
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Details</h2>

            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-500 text-sm">Date</span>
                    <span class="text-gray-200 text-sm font-mono">{{ $flight->flight_date }}</span>
                </div>
                @if($flight->aircraft_type)
                    <div class="flex justify-between">
                        <span class="text-gray-500 text-sm">Aircraft</span>
                        <span class="text-gray-200 text-sm">{{ $flight->aircraft_type }}</span>
                    </div>
                @endif
                @if($flight->aircraft_reg)
                    <div class="flex justify-between">
                        <span class="text-gray-500 text-sm">Registration</span>
                        <span class="text-gray-200 text-sm font-mono">{{ $flight->aircraft_reg }}</span>
                    </div>
                @endif
                @if($flight->baggage_claim)
                    <div class="flex justify-between">
                        <span class="text-gray-500 text-sm">Baggage</span>
                        <span class="text-gray-200 text-sm font-mono">{{ $flight->baggage_claim }}</span>
                    </div>
                @endif
                @if($flight->delay_reason)
                    <div class="flex justify-between">
                        <span class="text-gray-500 text-sm">Delay Reason</span>
                        <span class="text-delayed text-sm capitalize">{{ $flight->delay_reason }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ═══════════════ FOOTER ═══════════════ --}}
    <div class="flex items-center justify-between text-xs text-gray-600">
        <span>Data: AviationStack / FlightAware · Predictions: Spark ML · Gates: Community</span>
        <span>Last updated: {{ $flight->updated_at->diffForHumans() }}</span>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (window.WIMFFrontend) {
            console.log('[WIMF] Flight detail page — Scala.js components mounting');
        }
    });
</script>
@endpush
