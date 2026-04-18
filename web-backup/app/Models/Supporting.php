<?php
// Remaining simple models in one file for reference
// In a real Laravel app each would be in its own file

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Airport extends Model
{
    protected $fillable = ['iata_code', 'icao_code', 'name', 'city', 'country', 'latitude', 'longitude', 'timezone', 'total_gates'];
    public function departures() { return $this->hasMany(Flight::class, 'departure_airport_id'); }
    public function arrivals()   { return $this->hasMany(Flight::class, 'arrival_airport_id'); }
}

class Airline extends Model
{
    protected $fillable = ['iata_code', 'icao_code', 'name', 'country', 'logo_url'];
    public function flights() { return $this->hasMany(Flight::class); }
}

class Corroboration extends Model
{
    use \Illuminate\Database\Eloquent\Concerns\HasUuids;
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['contribution_id', 'user_id', 'agrees'];
    protected $casts = ['agrees' => 'boolean'];
    public function contribution() { return $this->belongsTo(GateContribution::class, 'contribution_id'); }
    public function user() { return $this->belongsTo(User::class); }
}

class Prediction extends Model
{
    use \Illuminate\Database\Eloquent\Concerns\HasUuids;
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

class FlightWatch extends Model
{
    use \Illuminate\Database\Eloquent\Concerns\HasUuids;
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['user_id', 'flight_id', 'notify_gate_change', 'notify_delay', 'notify_status'];
    protected $casts = [
        'notify_gate_change' => 'boolean',
        'notify_delay'       => 'boolean',
        'notify_status'      => 'boolean',
    ];
    public function user()   { return $this->belongsTo(User::class); }
    public function flight() { return $this->belongsTo(Flight::class); }
}
