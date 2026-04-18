<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Prediction extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'flight_id', 'delay_probability', 'estimated_delay_min',
        'confidence_interval_low', 'confidence_interval_high',
        'primary_cause', 'secondary_cause', 'model_version',
        'feature_vector', 'weather_condition', 'wind_speed_kts',
        'visibility_miles', 'ceiling_feet',
    ];

    protected $casts = [
        'delay_probability' => 'float',
        'feature_vector'    => 'array',
    ];

    public function flight() { return $this->belongsTo(Flight::class); }
}
