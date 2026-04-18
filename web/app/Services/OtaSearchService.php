<?php

namespace App\Services;

use Illuminate\Http\Request;

/**
 * OtaSearchService — Generates mock bookable flight data for the booking OTA page.
 * Since we have no GDS/Amadeus backend, prices are mathematically derived from
 * the route hash. Platform deep-links point to real aggregators.
 */
class OtaSearchService
{
    private array $airports = [
        'DEL' => ['city' => 'New Delhi',   'name' => 'Indira Gandhi Intl',       'country' => 'India'],
        'BOM' => ['city' => 'Mumbai',       'name' => 'Chhatrapati Shivaji Intl', 'country' => 'India'],
        'BLR' => ['city' => 'Bangalore',    'name' => 'Kempegowda Intl',          'country' => 'India'],
        'HYD' => ['city' => 'Hyderabad',    'name' => 'Rajiv Gandhi Intl',        'country' => 'India'],
        'CCU' => ['city' => 'Kolkata',      'name' => 'Netaji Subhas Intl',       'country' => 'India'],
        'MAA' => ['city' => 'Chennai',      'name' => 'Chennai Intl',             'country' => 'India'],
        'AMD' => ['city' => 'Ahmedabad',    'name' => 'Sardar Vallabhbhai Patel', 'country' => 'India'],
        'COK' => ['city' => 'Kochi',        'name' => 'Cochin Intl',              'country' => 'India'],
        'GOI' => ['city' => 'Goa',          'name' => 'Goa Intl',                 'country' => 'India'],
        'PNQ' => ['city' => 'Pune',         'name' => 'Pune Airport',             'country' => 'India'],
        'JFK' => ['city' => 'New York',     'name' => 'John F. Kennedy Intl',     'country' => 'United States'],
        'LAX' => ['city' => 'Los Angeles',  'name' => 'Los Angeles Intl',         'country' => 'United States'],
        'ORD' => ['city' => 'Chicago',      'name' => "O'Hare Intl",              'country' => 'United States'],
        'SFO' => ['city' => 'San Francisco','name' => 'San Francisco Intl',       'country' => 'United States'],
        'LHR' => ['city' => 'London',       'name' => 'Heathrow Airport',         'country' => 'United Kingdom'],
        'CDG' => ['city' => 'Paris',        'name' => 'Charles de Gaulle',        'country' => 'France'],
        'FRA' => ['city' => 'Frankfurt',    'name' => 'Frankfurt Airport',        'country' => 'Germany'],
        'AMS' => ['city' => 'Amsterdam',    'name' => 'Schiphol Airport',         'country' => 'Netherlands'],
        'DXB' => ['city' => 'Dubai',        'name' => 'Dubai Intl',               'country' => 'UAE'],
        'DOH' => ['city' => 'Doha',         'name' => 'Hamad Intl',               'country' => 'Qatar'],
        'AUH' => ['city' => 'Abu Dhabi',    'name' => 'Zayed Intl',               'country' => 'UAE'],
        'SIN' => ['city' => 'Singapore',    'name' => 'Changi Airport',           'country' => 'Singapore'],
        'KUL' => ['city' => 'Kuala Lumpur', 'name' => 'KLIA',                     'country' => 'Malaysia'],
        'BKK' => ['city' => 'Bangkok',      'name' => 'Suvarnabhumi Airport',     'country' => 'Thailand'],
        'HND' => ['city' => 'Tokyo',        'name' => 'Haneda Airport',           'country' => 'Japan'],
        'NRT' => ['city' => 'Tokyo',        'name' => 'Narita Intl',              'country' => 'Japan'],
        'ICN' => ['city' => 'Seoul',        'name' => 'Incheon Intl',             'country' => 'South Korea'],
        'SYD' => ['city' => 'Sydney',       'name' => 'Kingsford Smith',          'country' => 'Australia'],
        'MEL' => ['city' => 'Melbourne',    'name' => 'Melbourne Airport',        'country' => 'Australia'],
    ];

    private array $airlines = [
        'IndiGo'             => ['iata' => '6E', 'type' => 'lcc',         'color' => '#1b177e', 'emoji' => 'IN'],
        'Air India'          => ['iata' => 'AI', 'type' => 'fullservice', 'color' => '#c1272d', 'emoji' => 'AI'],
        'SpiceJet'           => ['iata' => 'SG', 'type' => 'lcc',         'color' => '#e8262d', 'emoji' => 'SJ'],
        'Vistara'            => ['iata' => 'UK', 'type' => 'fullservice', 'color' => '#6a2c91', 'emoji' => 'VS'],
        'Air Asia India'     => ['iata' => 'I5', 'type' => 'lcc',         'color' => '#ff0000', 'emoji' => 'AA'],
        'Emirates'           => ['iata' => 'EK', 'type' => 'fullservice', 'color' => '#c60c30', 'emoji' => 'EM'],
        'British Airways'    => ['iata' => 'BA', 'type' => 'fullservice', 'color' => '#2b5286', 'emoji' => 'BA'],
        'Lufthansa'          => ['iata' => 'LH', 'type' => 'fullservice', 'color' => '#00a5c8', 'emoji' => 'LH'],
        'Qatar Airways'      => ['iata' => 'QR', 'type' => 'fullservice', 'color' => '#5c0632', 'emoji' => 'QR'],
        'Singapore Airlines' => ['iata' => 'SQ', 'type' => 'fullservice', 'color' => '#003580', 'emoji' => 'SQ'],
        'Etihad Airways'     => ['iata' => 'EY', 'type' => 'fullservice', 'color' => '#bd8b13', 'emoji' => 'EY'],
        'Air Asia'           => ['iata' => 'AK', 'type' => 'lcc',         'color' => '#ff0000', 'emoji' => 'AK'],
        'Flydubai'           => ['iata' => 'FZ', 'type' => 'lcc',         'color' => '#d4373e', 'emoji' => 'FZ'],
    ];

    public function getAirportInfo(string $code): array
    {
        return $this->airports[strtoupper($code)] ?? [
            'city'    => $code,
            'name'    => "$code Airport",
            'country' => 'Unknown',
        ];
    }

    public function getAirportList(): array
    {
        return $this->airports;
    }

    public function search(string $from, string $to, string $date, string $cls = 'economy'): array
    {
        $from = strtoupper(trim($from));
        $to   = strtoupper(trim($to));
        
        // Map city names to IATA codes if provided
        $from = $this->resolveCityToIata($from);
        $to   = $this->resolveCityToIata($to);

        $fromInfo = $this->getAirportInfo($from);
        $toInfo   = $this->getAirportInfo($to);
        $isIntl   = $fromInfo['country'] !== $toInfo['country'];

        // Live Real-Time Integration via SerpApi
        $apiKey = env('SERPAPI_KEY') ?: config('app.serpapi_key');
        if ($apiKey) {
            try {
                return $this->liveSearchFromSerpApi($from, $to, $date, $cls, $apiKey, $fromInfo, $toInfo);
            } catch (\Exception $e) {
                // If API fails, silently fall back to mock data
            }
        }

        $seed      = abs(crc32("{$from}-{$to}"));
        $basePrice = $isIntl ? (8000 + $seed % 30000) : (1500 + $seed % 6000);
        $classMult = match($cls) { 'business' => 3.8, 'first' => 7.2, default => 1.0 };
        $durH      = $isIntl ? (3 + $seed % 9) : (1 + $seed % 3);
        $durM      = $seed % 60;

        $departures = ['05:55', '07:30', '09:15', '11:45', '14:00', '16:30', '19:10', '21:55'];

        // Filter airlines by route type
        $eligible = collect($this->airlines)->filter(function ($al) use ($isIntl) {
            if (!$isIntl) {
                return in_array($al['iata'], ['6E', 'SG', 'AI', 'UK', 'I5']);
            }
            return true;
        })->take(7);

        $flights = [];
        $i = 0;

        foreach ($eligible as $name => $al) {
            $dep    = $departures[$i % count($departures)];
            [$dh, $dm] = explode(':', $dep);
            $arrTs  = mktime((int)$dh, (int)$dm + $durM, 0) + ($durH * 3600);
            $arr    = date('H:i', $arrTs);
            $mult   = $al['type'] === 'lcc'
                ? 0.72 + (($seed + $i * 111) % 28) / 100
                : 0.95 + (($seed + $i * 77) % 20) / 100;
            $price  = (int) round($basePrice * $classMult * $mult, -2);
            $fnum   = strtoupper($al['iata']) . (($seed + $i * 37) % 900 + 100);
            $stops  = ($isIntl && $durH > 8) ? 1 : 0;

            // Build per-platform prices (sorted cheapest first)
            $platforms = $this->buildPlatformLinks($from, $to, $date, $price, -2);
            usort($platforms, fn($a, $b) => $a['price'] <=> $b['price']);

            $flights[] = [
                'id'         => $i + 1,
                'flight_num' => $fnum,
                'airline'    => $name,
                'iata'       => $al['iata'],
                'color'      => $al['color'],
                'abbr'       => $al['emoji'],
                'type'       => $al['type'],
                'from'       => $from,
                'to'         => $to,
                'from_city'  => $fromInfo['city'],
                'to_city'    => $toInfo['city'],
                'from_name'  => $fromInfo['name'],
                'to_name'    => $toInfo['name'],
                'departure'  => $dep,
                'arrival'    => $arr,
                'duration'   => "{$durH}h {$durM}m",
                'stops'      => $stops,
                'class'      => $cls,
                'base_price' => $price,
                'currency'   => 'INR',
                'platforms'  => $platforms,
                'date'       => $date,
            ];
            $i++;
        }

        // Sort cheapest first by default
        usort($flights, fn($a, $b) => $a['base_price'] <=> $b['base_price']);

        return [
            'flights'   => $flights,
            'from'      => $from,
            'to'        => $to,
            'from_info' => $fromInfo,
            'to_info'   => $toInfo,
            'date'      => $date,
            'class'     => $cls,
            'count'     => count($flights),
        ];
    }

    private function liveSearchFromSerpApi(string $from, string $to, string $date, string $cls, string $apiKey, array $fromInfo, array $toInfo): array
    {
        // 1=Economy, 2=Premium Economy, 3=Business, 4=First
        $travelClass = match(strtolower($cls)) {
            'business' => 3,
            'first' => 4,
            'premium economy' => 2,
            default => 1,
        };

        $params = [
            'engine' => 'google_flights',
            'departure_id' => $from,
            'arrival_id' => $to,
            'outbound_date' => $date,
            'currency' => 'INR',
            'hl' => 'en',
            'api_key' => $apiKey,
            'type' => 2, // One Way
            'travel_class' => $travelClass
        ];

        $url = 'https://serpapi.com/search.json?' . http_build_query($params);
        
        $json = \Illuminate\Support\Facades\Http::withoutVerifying()->timeout(10)->get($url)->json();

        if (empty($json['best_flights'])) {
            throw new \Exception("No flights found in SerpApi");
        }

        $flights = [];
        $i = 1;

        // Combine Best Flights and Other Flights
        $allRawFlights = array_merge($json['best_flights'] ?? [], $json['other_flights'] ?? []);

        foreach ($allRawFlights as $rawFlight) {
            $f = $rawFlight['flights'][0] ?? null;
            if (!$f) continue;
            
            $price = $rawFlight['price'] ?? 0;
            $airlineName = $f['airline'] ?? 'Unknown';
            $durationMins = $f['duration'] ?? 0;
            
            $durH = floor($durationMins / 60);
            $durM = $durationMins % 60;
            
            $depTime = isset($f['departure_airport']['time']) ? date('H:i', strtotime($f['departure_airport']['time'])) : '00:00';
            $arrTime = isset($f['arrival_airport']['time']) ? date('H:i', strtotime($f['arrival_airport']['time'])) : '00:00';
            
            // Get local airline info if exists
            $localAirlineInfo = collect($this->airlines)->firstWhere('iata', explode(' ', $f['flight_number'])[0] ?? '') ?? $this->airlines[$airlineName] ?? ['iata' => 'XX', 'type' => 'fullservice', 'color' => '#888888', 'emoji' => '✈️'];

            // Simulate OTA prices organically hovering around the Google Flights canonical price
            $platforms = $this->buildPlatformLinks($from, $to, $date, $price, -1);
            usort($platforms, fn($a, $b) => $a['price'] <=> $b['price']);

            $flights[] = [
                'id'         => $i,
                'flight_num' => $f['flight_number'] ?? 'N/A',
                'airline'    => $airlineName,
                'iata'       => $localAirlineInfo['iata'],
                'color'      => $localAirlineInfo['color'],
                'abbr'       => $localAirlineInfo['emoji'],
                'type'       => $localAirlineInfo['type'],
                'from'       => $from,
                'to'         => $to,
                'from_city'  => $fromInfo['city'],
                'to_city'    => $toInfo['city'],
                'from_name'  => $f['departure_airport']['name'] ?? $fromInfo['name'],
                'to_name'    => $f['arrival_airport']['name'] ?? $toInfo['name'],
                'departure'  => $depTime,
                'arrival'    => $arrTime,
                'duration'   => "{$durH}h {$durM}m",
                'stops'      => max(0, count($rawFlight['flights']) - 1),
                'class'      => $cls,
                'base_price' => $price,
                'currency'   => 'INR',
                'platforms'  => $platforms,
                'date'       => $date,
                'real_time'  => true
            ];
            $i++;
        }

        return [
            'flights'   => $flights,
            'from'      => $from,
            'to'        => $to,
            'from_info' => $fromInfo,
            'to_info'   => $toInfo,
            'date'      => $date,
            'class'     => $cls,
            'count'     => count($flights),
            'live_data' => true
        ];
    }

    private function resolveCityToIata(string $input): string
    {
        if (strlen($input) === 3) return $input;
        
        $inputLower = strtolower($input);
        foreach ($this->airports as $iata => $info) {
            if (strtolower($info['city']) === $inputLower || strtolower($info['name']) === $inputLower) {
                return $iata;
            }
        }
        
        // Fallback: partial match
        foreach ($this->airports as $iata => $info) {
            if (str_contains(strtolower($info['city']), $inputLower) || str_contains(strtolower($info['name']), $inputLower)) {
                return $iata;
            }
        }
        
        // If not found, return original (might fail gracefully or SerpApi might handle it somehow)
        return $input;
    }

    private function buildPlatformLinks(string $from, string $to, string $date, int $price, int $roundTo = -1): array
    {
        try {
            $dateObj = new \DateTime($date);
        } catch (\Exception $e) {
            $dateObj = new \DateTime(); // fallback
        }
        
        $dateGoogle = $dateObj->format('Y-m-d');
        $dateSky    = $dateObj->format('ymd');
        $dateMmt    = $dateObj->format('d/m/Y');
        $dateGoi    = $dateObj->format('Ymd');
        $dateKayak  = $dateObj->format('Y-m-d');

        return [
            ['name' => 'Google Flights', 'url' => "https://www.google.com/travel/flights?q=Flights%20to%20{$to}%20from%20{$from}%20on%20{$dateGoogle}", 'color' => '#4285f4', 'price' => $price],
            ['name' => 'Skyscanner',     'url' => "https://www.skyscanner.co.in/transport/flights/{$from}/{$to}/{$dateSky}/", 'color' => '#00a7de', 'price' => $price],
            ['name' => 'MakeMyTrip',     'url' => "https://www.makemytrip.com/flight/search?itinerary={$from}-{$to}-{$dateMmt}&tripType=O&paxType=A-1_C-0_I-0&intl=false&cabinClass=E", 'color' => '#e63946', 'price' => $price],
            ['name' => 'Goibibo',        'url' => "https://www.goibibo.com/flights/air-{$from}-{$to}-{$dateGoi}--1-0-0-E-D/", 'color' => '#1e7e34', 'price' => $price],
            ['name' => 'Kayak',          'url' => "https://www.kayak.co.in/flights/{$from}-{$to}/{$dateKayak}", 'color' => '#ff690f', 'price' => $price],
        ];
    }
}
