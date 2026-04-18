<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Airline extends Model
{
    protected $fillable = ['iata_code', 'icao_code', 'name', 'country', 'logo_url'];
    public function flights() { return $this->hasMany(Flight::class); }
}
