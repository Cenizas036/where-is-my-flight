<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Flight model — Core flight record.
 * 
 * Tracks scheduled, estimated, and actual times along with
 * gate assignments, delay info, and external API references.
 */
class Flight extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'flight_number',
        'airline_id',
        'departure_airport_id',
        'arrival_airport_id',
        'scheduled_departure',
        'scheduled_arrival',
        'actual_departure',
        'actual_arrival',
        'estimated_departure',
        'estimated_arrival',
        'status',
        'departure_gate',
        'arrival_gate',
        'departure_terminal',
        'arrival_terminal',
        'baggage_claim',
        'aircraft_type',
        'aircraft_reg',
        'delay_minutes',
        'delay_reason',
        'external_id',
        'flight_date',
    ];

    protected $casts = [
        'scheduled_departure' => 'datetime',
        'scheduled_arrival'   => 'datetime',
        'actual_departure'    => 'datetime',
        'actual_arrival'      => 'datetime',
        'estimated_departure' => 'datetime',
        'estimated_arrival'   => 'datetime',
        'flight_date'         => 'date',
        'delay_minutes'       => 'integer',
    ];

    // ── Relationships ──

    public function airline()
    {
        return $this->belongsTo(Airline::class);
    }

    public function departureAirport()
    {
        return $this->belongsTo(Airport::class, 'departure_airport_id');
    }

    public function arrivalAirport()
    {
        return $this->belongsTo(Airport::class, 'arrival_airport_id');
    }

    public function gateContributions()
    {
        return $this->hasMany(GateContribution::class);
    }

    public function predictions()
    {
        return $this->hasMany(Prediction::class);
    }

    public function watchers()
    {
        return $this->hasMany(FlightWatch::class);
    }

    // ── Scopes ──

    public function scopeToday($query)
    {
        return $query->where('flight_date', now()->format('Y-m-d'));
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['arrived', 'cancelled']);
    }

    public function scopeDelayed($query)
    {
        return $query->where('status', 'delayed');
    }

    // ── Computed ──

    public function isDelayed(): bool
    {
        return $this->status === 'delayed' || $this->delay_minutes > 0;
    }

    public function latestPrediction()
    {
        return $this->predictions()->latest()->first();
    }

    /**
     * Get the best gate info — official if available, else highest-confidence community.
     */
    public function effectiveGate(): ?string
    {
        if ($this->departure_gate) {
            return $this->departure_gate;
        }

        $communityGate = $this->gateContributions()
            ->where('is_live', true)
            ->orderBy('confidence_score', 'desc')
            ->first();

        return $communityGate?->gate_number;
    }
}
