<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Airports ──
        $airports = [
            ['iata_code' => 'JFK', 'icao_code' => 'KJFK', 'name' => 'John F. Kennedy International Airport', 'city' => 'New York', 'country' => 'US', 'latitude' => 40.6413, 'longitude' => -73.7781, 'timezone' => 'America/New_York', 'total_gates' => 128],
            ['iata_code' => 'LAX', 'icao_code' => 'KLAX', 'name' => 'Los Angeles International Airport', 'city' => 'Los Angeles', 'country' => 'US', 'latitude' => 33.9425, 'longitude' => -118.4081, 'timezone' => 'America/Los_Angeles', 'total_gates' => 132],
            ['iata_code' => 'LHR', 'icao_code' => 'EGLL', 'name' => 'London Heathrow Airport', 'city' => 'London', 'country' => 'GB', 'latitude' => 51.4700, 'longitude' => -0.4543, 'timezone' => 'Europe/London', 'total_gates' => 191],
            ['iata_code' => 'DXB', 'icao_code' => 'OMDB', 'name' => 'Dubai International Airport', 'city' => 'Dubai', 'country' => 'AE', 'latitude' => 25.2532, 'longitude' => 55.3657, 'timezone' => 'Asia/Dubai', 'total_gates' => 224],
            ['iata_code' => 'DEL', 'icao_code' => 'VIDP', 'name' => 'Indira Gandhi International Airport', 'city' => 'New Delhi', 'country' => 'IN', 'latitude' => 28.5562, 'longitude' => 77.1000, 'timezone' => 'Asia/Kolkata', 'total_gates' => 78],
            ['iata_code' => 'SIN', 'icao_code' => 'WSSS', 'name' => 'Singapore Changi Airport', 'city' => 'Singapore', 'country' => 'SG', 'latitude' => 1.3644, 'longitude' => 103.9915, 'timezone' => 'Asia/Singapore', 'total_gates' => 135],
            ['iata_code' => 'ORD', 'icao_code' => 'KORD', 'name' => 'O\'Hare International Airport', 'city' => 'Chicago', 'country' => 'US', 'latitude' => 41.9742, 'longitude' => -87.9073, 'timezone' => 'America/Chicago', 'total_gates' => 191],
            ['iata_code' => 'NRT', 'icao_code' => 'RJAA', 'name' => 'Narita International Airport', 'city' => 'Tokyo', 'country' => 'JP', 'latitude' => 35.7647, 'longitude' => 140.3864, 'timezone' => 'Asia/Tokyo', 'total_gates' => 99],
        ];

        foreach ($airports as $a) {
            $a['created_at'] = now();
            $a['updated_at'] = now();
            DB::table('airports')->insert($a);
        }

        // ── Airlines ──
        $airlines = [
            ['iata_code' => 'AA', 'icao_code' => 'AAL', 'name' => 'American Airlines', 'country' => 'US'],
            ['iata_code' => 'UA', 'icao_code' => 'UAL', 'name' => 'United Airlines', 'country' => 'US'],
            ['iata_code' => 'DL', 'icao_code' => 'DAL', 'name' => 'Delta Air Lines', 'country' => 'US'],
            ['iata_code' => 'BA', 'icao_code' => 'BAW', 'name' => 'British Airways', 'country' => 'GB'],
            ['iata_code' => 'EK', 'icao_code' => 'UAE', 'name' => 'Emirates', 'country' => 'AE'],
            ['iata_code' => 'SQ', 'icao_code' => 'SIA', 'name' => 'Singapore Airlines', 'country' => 'SG'],
            ['iata_code' => 'AI', 'icao_code' => 'AIC', 'name' => 'Air India', 'country' => 'IN'],
            ['iata_code' => 'JL', 'icao_code' => 'JAL', 'name' => 'Japan Airlines', 'country' => 'JP'],
        ];

        foreach ($airlines as $al) {
            $al['created_at'] = now();
            $al['updated_at'] = now();
            DB::table('airlines')->insert($al);
        }

        // Get inserted IDs
        $jfk = DB::table('airports')->where('iata_code', 'JFK')->value('id');
        $lax = DB::table('airports')->where('iata_code', 'LAX')->value('id');
        $lhr = DB::table('airports')->where('iata_code', 'LHR')->value('id');
        $dxb = DB::table('airports')->where('iata_code', 'DXB')->value('id');
        $del = DB::table('airports')->where('iata_code', 'DEL')->value('id');
        $sin = DB::table('airports')->where('iata_code', 'SIN')->value('id');
        $ord = DB::table('airports')->where('iata_code', 'ORD')->value('id');
        $nrt = DB::table('airports')->where('iata_code', 'NRT')->value('id');

        $aa = DB::table('airlines')->where('iata_code', 'AA')->value('id');
        $ua = DB::table('airlines')->where('iata_code', 'UA')->value('id');
        $dl = DB::table('airlines')->where('iata_code', 'DL')->value('id');
        $ba = DB::table('airlines')->where('iata_code', 'BA')->value('id');
        $ek = DB::table('airlines')->where('iata_code', 'EK')->value('id');
        $sq = DB::table('airlines')->where('iata_code', 'SQ')->value('id');
        $ai = DB::table('airlines')->where('iata_code', 'AI')->value('id');
        $jl = DB::table('airlines')->where('iata_code', 'JL')->value('id');

        $today = now()->format('Y-m-d');

        // ── Flights ──
        $flights = [
            [
                'id' => Str::uuid(), 'flight_number' => 'AA100', 'airline_id' => $aa,
                'departure_airport_id' => $jfk, 'arrival_airport_id' => $lhr,
                'scheduled_departure' => now()->setTime(8, 0), 'scheduled_arrival' => now()->setTime(20, 15),
                'status' => 'boarding', 'departure_gate' => 'A12', 'departure_terminal' => '8',
                'aircraft_type' => 'B777', 'flight_date' => $today, 'delay_minutes' => 0,
            ],
            [
                'id' => Str::uuid(), 'flight_number' => 'BA178', 'airline_id' => $ba,
                'departure_airport_id' => $jfk, 'arrival_airport_id' => $lhr,
                'scheduled_departure' => now()->setTime(10, 30), 'scheduled_arrival' => now()->setTime(22, 45),
                'status' => 'scheduled', 'departure_gate' => 'B7', 'departure_terminal' => '7',
                'aircraft_type' => 'A380', 'flight_date' => $today, 'delay_minutes' => 0,
            ],
            [
                'id' => Str::uuid(), 'flight_number' => 'EK201', 'airline_id' => $ek,
                'departure_airport_id' => $jfk, 'arrival_airport_id' => $dxb,
                'scheduled_departure' => now()->setTime(11, 0), 'scheduled_arrival' => now()->addDay()->setTime(7, 30),
                'status' => 'delayed', 'departure_gate' => null, 'departure_terminal' => '1',
                'aircraft_type' => 'A380', 'flight_date' => $today, 'delay_minutes' => 45,
                'delay_reason' => 'Weather conditions at departure airport',
            ],
            [
                'id' => Str::uuid(), 'flight_number' => 'DL400', 'airline_id' => $dl,
                'departure_airport_id' => $jfk, 'arrival_airport_id' => $lax,
                'scheduled_departure' => now()->setTime(7, 15), 'scheduled_arrival' => now()->setTime(10, 45),
                'status' => 'in_air', 'departure_gate' => 'C22', 'departure_terminal' => '4',
                'actual_departure' => now()->setTime(7, 20),
                'aircraft_type' => 'A321', 'flight_date' => $today, 'delay_minutes' => 5,
            ],
            [
                'id' => Str::uuid(), 'flight_number' => 'UA302', 'airline_id' => $ua,
                'departure_airport_id' => $jfk, 'arrival_airport_id' => $ord,
                'scheduled_departure' => now()->setTime(9, 0), 'scheduled_arrival' => now()->setTime(11, 30),
                'status' => 'delayed', 'departure_gate' => null, 'departure_terminal' => '7',
                'aircraft_type' => 'B737', 'flight_date' => $today, 'delay_minutes' => 90,
                'delay_reason' => 'Air traffic control restrictions',
            ],
            [
                'id' => Str::uuid(), 'flight_number' => 'SQ21', 'airline_id' => $sq,
                'departure_airport_id' => $jfk, 'arrival_airport_id' => $sin,
                'scheduled_departure' => now()->setTime(22, 45), 'scheduled_arrival' => now()->addDay()->setTime(22, 0),
                'status' => 'scheduled', 'departure_gate' => 'A8', 'departure_terminal' => '1',
                'aircraft_type' => 'A350', 'flight_date' => $today, 'delay_minutes' => 0,
            ],
            [
                'id' => Str::uuid(), 'flight_number' => 'AI101', 'airline_id' => $ai,
                'departure_airport_id' => $jfk, 'arrival_airport_id' => $del,
                'scheduled_departure' => now()->setTime(14, 30), 'scheduled_arrival' => now()->addDay()->setTime(14, 0),
                'status' => 'scheduled', 'departure_gate' => 'B3', 'departure_terminal' => '4',
                'aircraft_type' => 'B777', 'flight_date' => $today, 'delay_minutes' => 0,
            ],
            [
                'id' => Str::uuid(), 'flight_number' => 'JL5', 'airline_id' => $jl,
                'departure_airport_id' => $jfk, 'arrival_airport_id' => $nrt,
                'scheduled_departure' => now()->setTime(13, 0), 'scheduled_arrival' => now()->addDay()->setTime(16, 25),
                'status' => 'delayed', 'departure_gate' => 'A22', 'departure_terminal' => '1',
                'aircraft_type' => 'B787', 'flight_date' => $today, 'delay_minutes' => 25,
                'delay_reason' => 'Late arriving aircraft',
            ],
            // LAX departures
            [
                'id' => Str::uuid(), 'flight_number' => 'AA200', 'airline_id' => $aa,
                'departure_airport_id' => $lax, 'arrival_airport_id' => $jfk,
                'scheduled_departure' => now()->setTime(6, 0), 'scheduled_arrival' => now()->setTime(14, 30),
                'status' => 'arrived', 'departure_gate' => 'D44', 'departure_terminal' => '4',
                'actual_departure' => now()->setTime(6, 5), 'actual_arrival' => now()->setTime(14, 22),
                'aircraft_type' => 'B777', 'flight_date' => $today, 'delay_minutes' => 0,
            ],
            [
                'id' => Str::uuid(), 'flight_number' => 'DL500', 'airline_id' => $dl,
                'departure_airport_id' => $lax, 'arrival_airport_id' => $jfk,
                'scheduled_departure' => now()->setTime(16, 0), 'scheduled_arrival' => now()->addHours(5)->setTime(0, 30),
                'status' => 'scheduled', 'departure_gate' => null, 'departure_terminal' => '5',
                'aircraft_type' => 'A330', 'flight_date' => $today, 'delay_minutes' => 0,
            ],
            [
                'id' => Str::uuid(), 'flight_number' => 'UA800', 'airline_id' => $ua,
                'departure_airport_id' => $lax, 'arrival_airport_id' => $nrt,
                'scheduled_departure' => now()->setTime(12, 0), 'scheduled_arrival' => now()->addDay()->setTime(16, 0),
                'status' => 'in_air', 'departure_gate' => 'B71', 'departure_terminal' => 'B',
                'actual_departure' => now()->setTime(12, 10),
                'aircraft_type' => 'B787', 'flight_date' => $today, 'delay_minutes' => 10,
            ],
            [
                'id' => Str::uuid(), 'flight_number' => 'EK216', 'airline_id' => $ek,
                'departure_airport_id' => $lax, 'arrival_airport_id' => $dxb,
                'scheduled_departure' => now()->setTime(16, 30), 'scheduled_arrival' => now()->addDay()->setTime(17, 15),
                'status' => 'delayed', 'departure_gate' => null, 'departure_terminal' => 'B',
                'aircraft_type' => 'A380', 'flight_date' => $today, 'delay_minutes' => 60,
                'delay_reason' => 'Crew scheduling delay',
            ],
        ];

        foreach ($flights as $f) {
            $f['created_at'] = now();
            $f['updated_at'] = now();
            DB::table('flights')->insert($f);
        }

        // ── Demo User ──
        $userId = Str::uuid()->toString();
        DB::table('users')->insert([
            'id'           => $userId,
            'name'         => 'Demo User',
            'display_name' => 'Demo User',
            'email'        => 'demo@wimf.app',
            'password'     => Hash::make('password'),
            'password_hash' => Hash::make('password'),
            'is_moderator' => true,
            'trust_level'  => 5,
            'total_contributions' => 12,
            'accurate_contributions' => 10,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        // ── Demo Gate Contributions ──
        $ek201 = DB::table('flights')->where('flight_number', 'EK201')->value('id');
        $ua302 = DB::table('flights')->where('flight_number', 'UA302')->value('id');
        $dl500 = DB::table('flights')->where('flight_number', 'DL500')->value('id');

        if ($ek201) {
            DB::table('gate_contributions')->insert([
                'id' => Str::uuid(), 'flight_id' => $ek201, 'user_id' => $userId,
                'gate_number' => 'A5', 'terminal' => '1', 'contribution_type' => 'gate_update',
                'confidence_score' => 0.72, 'is_live' => true, 'corroboration_count' => 2,
                'created_at' => now()->subMinutes(15), 'updated_at' => now()->subMinutes(15),
            ]);
        }

        if ($ua302) {
            DB::table('gate_contributions')->insert([
                'id' => Str::uuid(), 'flight_id' => $ua302, 'user_id' => $userId,
                'gate_number' => 'C18', 'terminal' => '7', 'contribution_type' => 'gate_update',
                'confidence_score' => 0.88, 'is_verified' => true, 'is_live' => true,
                'corroboration_count' => 5,
                'created_at' => now()->subMinutes(30), 'updated_at' => now()->subMinutes(30),
            ]);
        }

        if ($dl500) {
            DB::table('gate_contributions')->insert([
                'id' => Str::uuid(), 'flight_id' => $dl500, 'user_id' => $userId,
                'gate_number' => 'B42', 'terminal' => '5', 'contribution_type' => 'gate_update',
                'confidence_score' => 0.65, 'is_live' => true, 'corroboration_count' => 1,
                'created_at' => now()->subMinutes(5), 'updated_at' => now()->subMinutes(5),
            ]);
        }

        // ── Trust Score ──
        DB::table('trust_scores')->insert([
            'id' => Str::uuid(), 'user_id' => $userId,
            'accuracy_rate' => 0.8333, 'recency_weight' => 0.95,
            'volume_bonus' => 0.012, 'composite_score' => 0.7867,
            'total_contributions' => 12, 'verified_contributions' => 10,
            'disputed_contributions' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
