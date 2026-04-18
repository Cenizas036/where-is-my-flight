@extends('layouts.app')
@section('title', 'Moderation Queue')

@section('content')
<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-white">Moderation Queue</h1>
        <p class="text-gray-400 text-sm mt-1">Review pending gate contributions</p>
    </div>

    @if($queue->count() > 0)
        <div class="rounded-2xl bg-gray-900/50 border border-gray-800/50 overflow-hidden">
            {{-- Header --}}
            <div class="grid grid-cols-12 gap-4 px-6 py-3 bg-gray-900 border-b border-gray-800 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                <div class="col-span-2">Flight</div>
                <div class="col-span-2">Gate</div>
                <div class="col-span-2">Contributor</div>
                <div class="col-span-2">Confidence</div>
                <div class="col-span-2">Submitted</div>
                <div class="col-span-2">Actions</div>
            </div>

            @foreach($queue as $contrib)
                <div class="grid grid-cols-12 gap-4 px-6 py-4 border-b border-gray-800/30 hover:bg-gray-800/20 transition-colors board-row">
                    {{-- Flight --}}
                    <div class="col-span-2">
                        <a href="{{ route('flights.show', ['flightNumber' => $contrib->flight->flight_number ?? '']) }}"
                           class="font-mono font-bold text-white hover:text-wimf-400 transition-colors">
                            {{ $contrib->flight->flight_number ?? '?' }}
                        </a>
                        <p class="text-xs text-gray-600">{{ $contrib->flight->scheduled_departure ? \Carbon\Carbon::parse($contrib->flight->scheduled_departure)->format('H:i') : '' }}</p>
                    </div>

                    {{-- Gate --}}
                    <div class="col-span-2">
                        <span class="gate-badge text-lg">{{ $contrib->gate_number }}</span>
                        @if($contrib->terminal)
                            <span class="text-xs text-gray-500 ml-2">T{{ $contrib->terminal }}</span>
                        @endif
                    </div>

                    {{-- Contributor --}}
                    <div class="col-span-2">
                        <p class="text-sm text-gray-300">{{ $contrib->user->display_name ?? 'Unknown' }}</p>
                        <div class="flex items-center space-x-1 mt-0.5">
                            <span class="text-xs text-gray-600">Trust:</span>
                            @php $trust = $contrib->user->trust_level ?? 1; @endphp
                            @for($i = 0; $i < 5; $i++)
                                <span class="text-xs {{ $i < $trust ? 'text-wimf-400' : 'text-gray-700' }}">★</span>
                            @endfor
                        </div>
                    </div>

                    {{-- Confidence --}}
                    <div class="col-span-2">
                        @php $pct = round($contrib->confidence_score * 100); @endphp
                        <div class="flex items-center space-x-2">
                            <div class="w-16 h-1.5 rounded-full bg-gray-800 overflow-hidden">
                                <div class="h-full rounded-full {{ $pct > 60 ? 'bg-ontime' : ($pct > 30 ? 'bg-delayed' : 'bg-cancelled') }}"
                                     style="width: {{ $pct }}%"></div>
                            </div>
                            <span class="text-xs font-mono text-gray-400">{{ $pct }}%</span>
                        </div>
                        <p class="text-xs text-gray-600 mt-0.5">{{ $contrib->corroboration_count }} {{ Str::plural('vote', $contrib->corroboration_count) }}</p>
                    </div>

                    {{-- Submitted --}}
                    <div class="col-span-2">
                        <p class="text-sm text-gray-400">{{ $contrib->created_at->diffForHumans() }}</p>
                    </div>

                    {{-- Actions --}}
                    <div class="col-span-2 flex items-center space-x-2">
                        <form method="POST" action="{{ route('mod.approve', $contrib) }}">
                            @csrf
                            <button class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-green-900/30 text-green-400 hover:bg-green-900/50 transition-colors border border-green-800/20">
                                Approve
                            </button>
                        </form>
                        <form method="POST" action="{{ route('mod.reject', $contrib) }}">
                            @csrf
                            <button class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-red-900/30 text-red-400 hover:bg-red-900/50 transition-colors border border-red-800/20">
                                Reject
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $queue->links() }}
        </div>
    @else
        <div class="text-center py-20">
            <p class="text-2xl text-gray-600">✓</p>
            <p class="text-gray-500 text-lg mt-2">Queue is clear — no pending contributions</p>
        </div>
    @endif
</div>
@endsection
