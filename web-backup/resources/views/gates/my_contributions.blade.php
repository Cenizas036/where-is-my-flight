@extends('layouts.app')
@section('title', 'My Contributions')

@section('content')
<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-white">My Contributions</h1>
        <p class="text-gray-400 text-sm mt-1">Your gate updates and their verification status</p>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-4">
        @php
            $total = $contributions->total();
            $verified = $contributions->getCollection()->where('is_verified', true)->where('is_live', true)->count();
            $pending = $contributions->getCollection()->where('is_verified', false)->count();
        @endphp
        <div class="glass-card rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-white">{{ $total }}</p>
            <p class="text-xs text-gray-500 mt-1">Total</p>
        </div>
        <div class="glass-card rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-ontime">{{ $verified }}</p>
            <p class="text-xs text-gray-500 mt-1">Verified</p>
        </div>
        <div class="glass-card rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-delayed">{{ $pending }}</p>
            <p class="text-xs text-gray-500 mt-1">Pending</p>
        </div>
    </div>

    @if($contributions->count() > 0)
        <div class="space-y-3 stagger-children">
            @foreach($contributions as $contrib)
                <div class="glass-card rounded-xl p-5">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-5">
                            {{-- Gate Badge --}}
                            <span class="gate-badge {{ $contrib->is_live ? 'gate-badge-community' : '' }} text-lg">
                                {{ $contrib->gate_number }}
                            </span>

                            {{-- Flight --}}
                            <div>
                                <a href="{{ route('flights.show', ['flightNumber' => $contrib->flight->flight_number ?? '']) }}"
                                   class="font-mono font-bold text-white hover:text-wimf-400 transition-colors">
                                    {{ $contrib->flight->flight_number ?? '?' }}
                                </a>
                                <p class="text-xs text-gray-600">
                                    {{ $contrib->flight->departureAirport->iata_code ?? '?' }} →
                                    {{ $contrib->flight->arrivalAirport->iata_code ?? '?' }}
                                </p>
                            </div>

                            {{-- Confidence --}}
                            <div>
                                @php $pct = round($contrib->confidence_score * 100); @endphp
                                <div class="flex items-center space-x-2">
                                    <div class="w-12 h-1.5 rounded-full bg-gray-800 overflow-hidden">
                                        <div class="h-full rounded-full {{ $pct > 60 ? 'bg-ontime' : ($pct > 30 ? 'bg-delayed' : 'bg-cancelled') }}"
                                             style="width: {{ $pct }}%"></div>
                                    </div>
                                    <span class="text-xs font-mono text-gray-400">{{ $pct }}%</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center space-x-3">
                            {{-- Status --}}
                            @if($contrib->is_verified && $contrib->is_live)
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-ontime/10 text-ontime">
                                    ✓ Verified & Live
                                </span>
                            @elseif($contrib->is_live)
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-wimf-600/10 text-wimf-400">
                                    Live (tentative)
                                </span>
                            @elseif($contrib->is_verified && !$contrib->is_live)
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-cancelled/10 text-cancelled">
                                    Rejected
                                </span>
                            @else
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-delayed/10 text-delayed">
                                    Pending Review
                                </span>
                            @endif

                            <span class="text-xs text-gray-600">{{ $contrib->created_at->diffForHumans() }}</span>
                        </div>
                    </div>

                    @if($contrib->moderation_note)
                        <p class="text-xs text-gray-500 mt-2 pl-16">Note: {{ $contrib->moderation_note }}</p>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $contributions->links() }}
        </div>
    @else
        <div class="text-center py-20">
            <p class="text-gray-500 text-lg">No contributions yet</p>
            <p class="text-gray-600 text-sm mt-2">Visit a flight page and submit a gate update to get started</p>
        </div>
    @endif
</div>
@endsection
