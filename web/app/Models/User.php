<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * User model — Passengers who track flights and contribute gate data.
 */
class User extends Authenticatable
{
    use HasUuids, Notifiable;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'email',
        'password',
        'password_hash',
        'display_name',
        'avatar_url',
        'trust_level',
        'is_verified',
        'is_moderator',
        'last_login_at',
        'total_contributions',
        'accurate_contributions',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'is_verified'   => 'boolean',
        'is_moderator'  => 'boolean',
        'trust_level'   => 'integer',
        'last_login_at' => 'datetime',
        'total_contributions'    => 'integer',
        'accurate_contributions' => 'integer',
    ];

    /**
     * Override the auth password field.
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    // ── Relationships ──

    public function trustScore()
    {
        return $this->hasOne(TrustScore::class);
    }

    public function gateContributions()
    {
        return $this->hasMany(GateContribution::class);
    }

    public function flightWatches()
    {
        return $this->hasMany(FlightWatch::class);
    }

    public function corroborations()
    {
        return $this->hasMany(Corroboration::class);
    }

    // ── Computed ──

    public function accuracyRate(): float
    {
        if ($this->total_contributions === 0) return 0.5;
        return $this->accurate_contributions / $this->total_contributions;
    }

    public function isModerator(): bool
    {
        return $this->is_moderator ?? false;
    }
}
