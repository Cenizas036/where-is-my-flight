<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * GateContribution — Community-submitted gate information.
 */
class GateContribution extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'flight_id', 'user_id', 'gate_number', 'terminal',
        'contribution_type', 'confidence_score', 'is_verified',
        'is_live', 'verified_by', 'moderation_note',
        'corroboration_count', 'latitude', 'longitude',
    ];

    protected $casts = [
        'confidence_score'    => 'float',
        'is_verified'         => 'boolean',
        'is_live'             => 'boolean',
        'corroboration_count' => 'integer',
        'latitude'            => 'float',
        'longitude'           => 'float',
    ];

    public function flight()   { return $this->belongsTo(Flight::class); }
    public function user()     { return $this->belongsTo(User::class); }
    public function verifier() { return $this->belongsTo(User::class, 'verified_by'); }
    public function corroborations() { return $this->hasMany(Corroboration::class, 'contribution_id'); }
}
