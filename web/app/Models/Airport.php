<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Airport extends Model
{
    protected $fillable = ['iata_code', 'icao_code', 'name', 'city', 'country', 'latitude', 'longitude', 'timezone', 'total_gates'];
    public function departures() { return $this->hasMany(Flight::class, 'departure_airport_id'); }
    public function arrivals()   { return $this->hasMany(Flight::class, 'arrival_airport_id'); }
}
