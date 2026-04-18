<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * FlightWatch — Tracks which flights a user is monitoring
 * for gate changes, delays, and status updates.
 */
class FlightWatch extends Model
{
    use HasUuids;

    protected $table = 'flight_watches';

    protected $fillable = [
        'user_id',
        'flight_id',
        'notify_gate_change',
        'notify_delay',
        'notify_status',
    ];

    protected $casts = [
        'notify_gate_change' => 'boolean',
        'notify_delay'       => 'boolean',
        'notify_status'      => 'boolean',
    ];

    public $timestamps = false;

    protected static function booted(): void
    {
        static::creating(function ($watch) {
            $watch->created_at = now();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function flight(): BelongsTo
    {
        return $this->belongsTo(Flight::class);
    }
}
