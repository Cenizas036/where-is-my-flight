<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Corroboration — A user's vote to confirm or dispute a gate contribution.
 * Unique constraint: one vote per user per contribution.
 */
class Corroboration extends Model
{
    use HasUuids;

    protected $table = 'corroborations';

    protected $fillable = [
        'contribution_id',
        'user_id',
        'agrees',
    ];

    protected $casts = [
        'agrees' => 'boolean',
    ];

    public $timestamps = false;

    protected static function booted(): void
    {
        static::creating(function ($corroboration) {
            $corroboration->created_at = now();
        });
    }

    public function contribution(): BelongsTo
    {
        return $this->belongsTo(GateContribution::class, 'contribution_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
