<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** TrustScore — User reliability metrics for the contribution system. */
class TrustScore extends Model
{
    use HasUuids;
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'accuracy_rate', 'recency_weight', 'volume_bonus',
        'composite_score', 'total_contributions', 'verified_contributions',
        'disputed_contributions',
    ];

    protected $casts = [
        'accuracy_rate'  => 'float',
        'recency_weight' => 'float',
        'volume_bonus'   => 'float',
        'composite_score' => 'float',
    ];

    public function user() { return $this->belongsTo(User::class); }
}
