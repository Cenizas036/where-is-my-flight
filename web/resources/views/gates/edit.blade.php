@extends('layouts.app')
@section('title', 'Edit Gate — ' . $flight->flight_number)

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    {{-- ═══════════════ HEADER ═══════════════ --}}
    <div>
        <a href="{{ route('flight.profile', ['callsign' => $flight->flight_number]) }}"
           class="text-sm text-gray-500 hover:text-gray-300 transition-colors">
            ← Back to {{ $flight->flight_number }}
        </a>
        <h1 class="text-2xl font-bold text-white mt-2">Report Gate Info</h1>
        <p class="text-gray-400 text-sm mt-1">
            Help other passengers by sharing gate information for
            <span class="text-white font-mono font-bold">{{ $flight->flight_number }}</span>
            ({{ $flight->departureAirport->iata_code ?? '?' }} → {{ $flight->arrivalAirport->iata_code ?? '?' }})
        </p>
    </div>

    {{-- ═══════════════ EXISTING CONTRIBUTIONS ═══════════════ --}}
    @if($contributions->count() > 0)
        <div class="glass-card rounded-2xl p-6">
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Current Reports</h2>
            <div class="space-y-3">
                @foreach($contributions as $contrib)
                    <div class="flex items-center justify-between rounded-lg bg-gray-900/50 border border-gray-800/50 px-4 py-3">
                        <div class="flex items-center space-x-4">
                            <span class="gate-badge gate-badge-community text-lg">{{ $contrib->gate_number }}</span>
                            @if($contrib->terminal)
                                <span class="text-sm text-gray-400">Terminal {{ $contrib->terminal }}</span>
                            @endif
                            <span class="text-xs text-gray-600">
                                {{ $contrib->user->display_name ?? 'User' }}
                                · {{ round($contrib->confidence_score * 100) }}% confidence
                                · {{ $contrib->corroboration_count }} {{ Str::plural('report', $contrib->corroboration_count) }}
                            </span>
                        </div>

                        <div class="flex items-center space-x-2">
                            <form method="POST" action="{{ route('gates.corroborate', $contrib) }}">
                                @csrf
                                <input type="hidden" name="agrees" value="1">
                                <button class="px-3 py-1.5 rounded-lg text-xs font-medium bg-green-900/30 text-green-400 hover:bg-green-900/50 transition-colors border border-green-800/30">
                                    ✓ Confirm
                                </button>
                            </form>
                            <form method="POST" action="{{ route('gates.corroborate', $contrib) }}">
                                @csrf
                                <input type="hidden" name="agrees" value="0">
                                <button class="px-3 py-1.5 rounded-lg text-xs font-medium bg-red-900/30 text-red-400 hover:bg-red-900/50 transition-colors border border-red-800/30">
                                    ✗ Dispute
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ═══════════════ SUBMISSION FORM ═══════════════ --}}
    <div class="glass-card rounded-2xl p-6">
        <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Submit Gate Update</h2>

        <form method="POST" action="{{ route('gates.submit') }}" class="space-y-5">
            @csrf
            <input type="hidden" name="flight_id" value="{{ $flight->id }}">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-sm text-gray-400 mb-2 block">Gate Number *</label>
                    <input type="text" name="gate_number"
                           value="{{ old('gate_number') }}"
                           placeholder="e.g. A12, B3, 42"
                           required maxlength="10"
                           class="w-full px-4 py-3 rounded-xl bg-gray-900 border border-gray-700 text-white font-mono text-xl text-center uppercase focus:border-wimf-500 focus:ring-2 focus:ring-wimf-500/20 outline-none transition-all wimf-input">
                    @error('gate_number')
                        <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-sm text-gray-400 mb-2 block">Terminal (optional)</label>
                    <input type="text" name="terminal"
                           value="{{ old('terminal') }}"
                           placeholder="e.g. T1, A, 2"
                           maxlength="10"
                           class="w-full px-4 py-3 rounded-xl bg-gray-900 border border-gray-700 text-white font-mono text-xl text-center uppercase focus:border-wimf-500 outline-none transition-all wimf-input">
                </div>
            </div>

            <input type="hidden" name="contribution_type" value="gate_update">

            <div class="pt-2">
                <button type="submit"
                        class="w-full py-3 rounded-xl bg-wimf-600 text-white font-semibold text-sm hover:bg-wimf-500 shadow-lg shadow-wimf-600/20 transition-all">
                    Submit Gate Update
                </button>
                <p class="text-xs text-gray-600 text-center mt-3">
                    Your contribution will be scored based on your trust level. High-trust updates go live immediately.
                </p>
            </div>
        </form>
    </div>
</div>
@endsection
