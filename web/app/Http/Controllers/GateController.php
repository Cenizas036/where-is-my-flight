<?php

namespace App\Http\Controllers;

use App\Models\Flight;
use App\Models\GateContribution;
use App\Models\Corroboration;
use App\Models\TrustScore;
use App\Http\Requests\GateUpdateRequest;
use App\Services\PlayApiClient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * GateController — Community gate contribution system.
 * 
 * Implements the trust scoring workflow:
 * 1. User submits gate update
 * 2. System calculates initial confidence from user trust score
 * 3. Other users can corroborate (agree/dispute)
 * 4. High-confidence edits go live immediately
 * 5. Borderline ones go to mod queue
 */
class GateController extends Controller
{
    private const AUTO_APPROVE_THRESHOLD = 0.85;
    private const LIVE_THRESHOLD = 0.65;
    private const CORROBORATION_BOOST = 0.08;
    private const CORROBORATION_PENALTY = 0.12;

    public function __construct(
        private readonly PlayApiClient $playApi
    ) {}

    /**
     * Show the gate edit form for a flight.
     */
    public function edit(Flight $flight)
    {
        $existingContributions = GateContribution::where('flight_id', $flight->id)
            ->where('is_live', true)
            ->with('user')
            ->orderBy('confidence_score', 'desc')
            ->get();

        return view('gates.edit', [
            'flight'       => $flight->load(['departureAirport', 'arrivalAirport', 'airline']),
            'contributions' => $existingContributions,
        ]);
    }

    /**
     * Submit a gate contribution.
     * Calculates initial confidence based on user's trust score and applies
     * auto-approval if the user is trusted enough.
     */
    public function submit(GateUpdateRequest $request)
    {
        $user = auth()->user();
        $trustScore = TrustScore::firstOrCreate(
            ['user_id' => $user->id],
            ['composite_score' => 0.5000]
        );

        // Calculate initial confidence from trust composite
        $initialConfidence = $this->calculateInitialConfidence($trustScore);

        $contribution = GateContribution::create([
            'flight_id'         => $request->flight_id,
            'user_id'           => $user->id,
            'gate_number'       => strtoupper($request->gate_number),
            'terminal'          => $request->terminal ? strtoupper($request->terminal) : null,
            'contribution_type' => $request->contribution_type ?? 'gate_update',
            'confidence_score'  => $initialConfidence,
            'latitude'          => $request->latitude,
            'longitude'         => $request->longitude,
        ]);

        // Auto-approve if high trust
        if ($initialConfidence >= self::AUTO_APPROVE_THRESHOLD) {
            $contribution->update([
                'is_verified' => true,
                'is_live'     => true,
            ]);

            // Push the update to live board via Play API → Redis → WebSocket
            $this->playApi->broadcastGateUpdate($contribution);

            // Update the flight record itself
            Flight::where('id', $request->flight_id)->update([
                'departure_gate' => $contribution->gate_number,
                'departure_terminal' => $contribution->terminal,
            ]);
        } elseif ($initialConfidence >= self::LIVE_THRESHOLD) {
            // Tentatively live, awaiting corroboration
            $contribution->update(['is_live' => true]);
        }

        // Increment user contribution count
        $user->increment('total_contributions');

        if ($request->expectsJson()) {
            return response()->json($contribution, 201);
        }

        return redirect()->route('flights.show', [
            'flightNumber' => Flight::find($request->flight_id)->flight_number,
        ])->with('success', 'Gate update submitted! ' . 
            ($contribution->is_live ? 'Your update is live.' : 'Awaiting verification.'));
    }

    /**
     * Corroborate (agree or dispute) a gate contribution.
     * Each corroboration adjusts the confidence score.
     */
    public function corroborate(Request $request, GateContribution $contribution)
    {
        $agrees = $request->boolean('agrees', true);

        // Create corroboration record (unique per user per contribution)
        Corroboration::updateOrCreate(
            [
                'contribution_id' => $contribution->id,
                'user_id'         => auth()->id(),
            ],
            ['agrees' => $agrees]
        );

        // Recalculate confidence
        $this->recalculateConfidence($contribution);

        if ($request->expectsJson()) {
            return response()->json(['confidence' => $contribution->fresh()->confidence_score]);
        }

        return back()->with('success', $agrees ? 'Confirmed!' : 'Disputed.');
    }

    /**
     * Display the moderation queue for moderators.
     */
    public function moderationQueue()
    {
        $queue = GateContribution::where('is_verified', false)
            ->with(['user', 'flight.departureAirport', 'flight.arrivalAirport'])
            ->orderBy('confidence_score', 'desc')
            ->orderBy('created_at', 'asc')
            ->paginate(30);

        return view('gates.moderation', ['queue' => $queue]);
    }

    /**
     * Approve a contribution from the mod queue.
     */
    public function approve(GateContribution $contribution)
    {
        $contribution->update([
            'is_verified' => true,
            'is_live'     => true,
            'verified_by' => auth()->id(),
        ]);

        // Update flight record
        $flight = Flight::find($contribution->flight_id);
        $flight->update([
            'departure_gate'     => $contribution->gate_number,
            'departure_terminal' => $contribution->terminal,
        ]);

        // Boost contributor's trust score
        $this->boostTrustScore($contribution->user_id);

        // Broadcast via Play API
        $this->playApi->broadcastGateUpdate($contribution);

        return back()->with('success', 'Contribution approved and live.');
    }

    /**
     * Reject a contribution from the mod queue.
     */
    public function reject(Request $request, GateContribution $contribution)
    {
        $contribution->update([
            'is_verified' => true,
            'is_live'     => false,
            'verified_by' => auth()->id(),
            'moderation_note' => $request->get('reason', 'Rejected by moderator'),
        ]);

        // Penalize contributor's trust score
        $this->penalizeTrustScore($contribution->user_id);

        return back()->with('success', 'Contribution rejected.');
    }

    /**
     * Display user's own contributions.
     */
    public function myContributions()
    {
        $contributions = GateContribution::where('user_id', auth()->id())
            ->with(['flight.departureAirport', 'flight.arrivalAirport'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('gates.my_contributions', ['contributions' => $contributions]);
    }

    // ─────────────────────────────────────────
    // API Methods (consumed by Scala.js)
    // ─────────────────────────────────────────

    public function apiSubmit(GateUpdateRequest $request): JsonResponse
    {
        // Reuse the submit logic but force JSON response
        $request->headers->set('Accept', 'application/json');
        return $this->submit($request);
    }

    public function apiCorroborate(Request $request, string $contributionId): JsonResponse
    {
        $contribution = GateContribution::findOrFail($contributionId);
        $request->headers->set('Accept', 'application/json');
        return $this->corroborate($request, $contribution);
    }

    public function apiGateInfo(string $flightId): JsonResponse
    {
        $flight = Flight::findOrFail($flightId);
        
        $officialGate = [
            'gate'     => $flight->departure_gate,
            'terminal' => $flight->departure_terminal,
            'source'   => 'official',
        ];

        $communityGates = GateContribution::where('flight_id', $flightId)
            ->where('is_live', true)
            ->with('user:id,display_name,trust_level')
            ->orderBy('confidence_score', 'desc')
            ->get()
            ->map(fn ($c) => [
                'gate'        => $c->gate_number,
                'terminal'    => $c->terminal,
                'confidence'  => $c->confidence_score,
                'contributor' => $c->user->display_name,
                'trust_level' => $c->user->trust_level,
                'reports'     => $c->corroboration_count,
                'source'      => 'community',
                'created_at'  => $c->created_at->toIso8601String(),
            ]);

        return response()->json([
            'official'  => $officialGate,
            'community' => $communityGates,
        ]);
    }

    // ─────────────────────────────────────────
    // Private: Trust & Confidence Calculations
    // ─────────────────────────────────────────

    /**
     * Calculate initial confidence score for a new contribution
     * based on the user's trust composite and recency.
     */
    private function calculateInitialConfidence(TrustScore $trust): float
    {
        // Base from user's composite trust score  
        $base = $trust->composite_score;

        // Volume bonus: users with many accurate contributions get a small boost
        $volumeBonus = min(0.05, $trust->verified_contributions * 0.001);

        // Recency weight (trust scores already factor this in)
        $confidence = $base + $volumeBonus;

        return round(min(1.0, max(0.0, $confidence)), 4);
    }

    /**
     * Recalculate a contribution's confidence based on corroborations.
     */
    private function recalculateConfidence(GateContribution $contribution): void
    {
        $corroborations = Corroboration::where('contribution_id', $contribution->id)->get();

        $agrees = $corroborations->where('agrees', true)->count();
        $disputes = $corroborations->where('agrees', false)->count();
        $total = $agrees + $disputes;

        if ($total === 0) return;

        // Start from current confidence and adjust
        $adjustment = ($agrees * self::CORROBORATION_BOOST) - ($disputes * self::CORROBORATION_PENALTY);
        $newConfidence = $contribution->confidence_score + $adjustment;
        $newConfidence = round(min(1.0, max(0.0, $newConfidence)), 4);

        $contribution->update([
            'confidence_score'    => $newConfidence,
            'corroboration_count' => $total,
            'is_live'             => $newConfidence >= self::LIVE_THRESHOLD,
        ]);

        // Auto-approve at threshold
        if ($newConfidence >= self::AUTO_APPROVE_THRESHOLD && !$contribution->is_verified) {
            $contribution->update(['is_verified' => true, 'is_live' => true]);

            Flight::where('id', $contribution->flight_id)->update([
                'departure_gate' => $contribution->gate_number,
            ]);

            $this->playApi->broadcastGateUpdate($contribution);
        }
    }

    /**
     * Boost a user's trust score after a verified contribution.
     */
    private function boostTrustScore(string $userId): void
    {
        $trust = TrustScore::firstOrCreate(
            ['user_id' => $userId],
            ['composite_score' => 0.5]
        );

        $trust->increment('verified_contributions');
        $trust->increment('total_contributions');

        // Recalculate composite
        $accuracy = $trust->total_contributions > 0
            ? $trust->verified_contributions / $trust->total_contributions
            : 0.5;

        $trust->update([
            'accuracy_rate'   => round($accuracy, 4),
            'composite_score' => round(min(1.0, $accuracy * 0.7 + $trust->volume_bonus * 0.3), 4),
        ]);
    }

    /**
     * Penalize a user's trust score after a rejected contribution.
     */
    private function penalizeTrustScore(string $userId): void
    {
        $trust = TrustScore::firstOrCreate(
            ['user_id' => $userId],
            ['composite_score' => 0.5]
        );

        $trust->increment('disputed_contributions');
        $trust->increment('total_contributions');

        $accuracy = $trust->total_contributions > 0
            ? $trust->verified_contributions / $trust->total_contributions
            : 0.5;

        $trust->update([
            'accuracy_rate'   => round($accuracy, 4),
            'composite_score' => round(min(1.0, max(0.1, $accuracy * 0.7 + $trust->volume_bonus * 0.3)), 4),
        ]);
    }
}
