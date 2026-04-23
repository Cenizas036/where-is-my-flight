<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * OpenSkyService — Fetches real-time flight data from the OpenSky Network REST API.
 *
 * Endpoint: https://opensky-network.org/api/states/all
 * Returns live ADS-B state vectors for aircraft worldwide.
 *
 * Unauthenticated access: ~100 req/day.
 * We cache aggressively (120s default, 600s on 429) plus provide fallback demo data.
 */
class OpenSkyService
{
    private string $baseUrl = 'https://opensky-network.org/api';

    /**
     * Build an authenticated HTTP request to OpenSky.
     */
    private function http()
    {
        $username = env('OPENSKY_USERNAME', '');
        $password = env('OPENSKY_PASSWORD', '');

        $request = Http::withoutVerifying()->timeout(15);

        if (!empty($username) && !empty($password)) {
            $request = $request->withBasicAuth($username, $password);
        }

        return $request;
    }

    /**
     * Get all live aircraft state vectors worldwide.
     * Cached for 120 seconds. On 429 rate-limit, caches empty for 10 min and uses fallback.
     *
     * @return array  Array of aircraft state arrays
     */
    public function getAllFlights(): array
    {
        // Check if we're in a rate-limit backoff period
        if (Cache::has('opensky:rate_limited')) {
            Log::debug('OpenSky: In rate-limit backoff, using demo data');
            return $this->getDemoFlights();
        }

        return Cache::remember('opensky:all', 120, function () {
            try {
                $response = $this->http()->get("{$this->baseUrl}/states/all");

                if ($response->successful()) {
                    $flights = $this->parseStates($response->json());
                    if (count($flights) > 0) {
                        Cache::forget('opensky:rate_limited');
                        return $flights;
                    }
                    
                    Log::warning('OpenSky API returned 0 flights', ['body' => $response->body()]);
                    return [];
                }

                if ($response->status() === 429) {
                    Log::warning('OpenSky API rate-limited (429). Backing off for 10 minutes.');
                    Cache::put('opensky:rate_limited', true, 600);
                    return $this->getDemoFlights();
                }

                Log::warning('OpenSky API failed', ['status' => $response->status()]);
                return $this->getDemoFlights();
            } catch (\Exception $e) {
                Log::error('OpenSky API error', ['error' => $e->getMessage()]);
                return $this->getDemoFlights();
            }
        });
    }

    /**
     * Get flights filtered by a bounding box (lat/lon).
     * Cached for 120 seconds.
     */
    public function getFlightsByBoundingBox(float $lamin, float $lomin, float $lamax, float $lomax): array
    {
        if (Cache::has('opensky:rate_limited')) {
            return array_values(array_filter($this->getDemoFlights(), function ($f) use ($lamin, $lomin, $lamax, $lomax) {
                return $f['latitude'] >= $lamin && $f['latitude'] <= $lamax
                    && $f['longitude'] >= $lomin && $f['longitude'] <= $lomax;
            }));
        }

        $cacheKey = "opensky:bbox:" . round($lamin, 1) . ":" . round($lomin, 1) . ":" . round($lamax, 1) . ":" . round($lomax, 1);

        return Cache::remember($cacheKey, 120, function () use ($lamin, $lomin, $lamax, $lomax) {
            try {
                $response = $this->http()->get("{$this->baseUrl}/states/all", [
                    'lamin' => $lamin,
                    'lomin' => $lomin,
                    'lamax' => $lamax,
                    'lomax' => $lomax,
                ]);

                if ($response->successful()) {
                    $flights = $this->parseStates($response->json());
                    if (count($flights) > 0) return $flights;
                }

                if ($response->status() === 429) {
                    Cache::put('opensky:rate_limited', true, 600);
                    return array_values(array_filter($this->getDemoFlights(), function ($f) use ($lamin, $lomin, $lamax, $lomax) {
                        return $f['latitude'] >= $lamin && $f['latitude'] <= $lamax
                            && $f['longitude'] >= $lomin && $f['longitude'] <= $lomax;
                    }));
                }

                return [];
            } catch (\Exception $e) {
                Log::error('OpenSky bbox error', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }

    /**
     * Get flights filtered by origin country.
     */
    public function getFlightsByCountry(?string $country = null): array
    {
        $all = $this->getAllFlights();

        if ($country && $country !== 'all') {
            return array_values(array_filter($all, function ($flight) use ($country) {
                return strcasecmp($flight['origin_country'], $country) === 0;
            }));
        }

        return $all;
    }

    /**
     * Get list of unique countries from current data.
     */
    public function getActiveCountries(): array
    {
        $flights = $this->getAllFlights();
        $countries = [];

        foreach ($flights as $flight) {
            $c = $flight['origin_country'];
            if (!isset($countries[$c])) {
                $countries[$c] = 0;
            }
            $countries[$c]++;
        }

        arsort($countries);
        return $countries;
    }

    /**
     * Get summary stats from current data.
     */
    public function getStats(): array
    {
        $flights = $this->getAllFlights();
        $countries = [];
        $altitudes = [];
        $speeds = [];

        foreach ($flights as $f) {
            $countries[$f['origin_country']] = true;
            if ($f['altitude'] > 0) $altitudes[] = $f['altitude'];
            if ($f['velocity'] > 0) $speeds[] = $f['velocity'];
        }

        return [
            'total_aircraft'  => count($flights),
            'total_countries' => count($countries),
            'avg_altitude_m'  => count($altitudes) > 0 ? round(array_sum($altitudes) / count($altitudes)) : 0,
            'avg_speed_kmh'   => count($speeds) > 0 ? round((array_sum($speeds) / count($speeds)) * 3.6) : 0,
        ];
    }

    /**
     * Check if we're currently using demo/fallback data.
     */
    public function isUsingFallback(): bool
    {
        return Cache::has('opensky:rate_limited');
    }

    /**
     * Clear the rate limit backoff (useful for manual retry).
     */
    public function clearRateLimit(): void
    {
        Cache::forget('opensky:rate_limited');
        Cache::forget('opensky:all');
    }

    /**
     * Parse OpenSky state vector response into a nice associative array.
     */
    private function parseStates(?array $data): array
    {
        if (!$data || !isset($data['states']) || !is_array($data['states'])) {
            return [];
        }

        $flights = [];

        foreach ($data['states'] as $state) {
            if (!isset($state[5]) || !isset($state[6]) || $state[5] === null || $state[6] === null) {
                continue;
            }

            $callsign = trim($state[1] ?? '');
            if (empty($callsign)) continue;

            $flights[] = [
                'icao24'         => $state[0] ?? '',
                'callsign'       => $callsign,
                'origin_country' => $state[2] ?? 'Unknown',
                'longitude'      => round((float)$state[5], 4),
                'latitude'       => round((float)$state[6], 4),
                'altitude'       => round((float)($state[7] ?? $state[13] ?? 0)),
                'on_ground'      => (bool)($state[8] ?? false),
                'velocity'       => round((float)($state[9] ?? 0), 1),
                'heading'        => round((float)($state[10] ?? 0)),
                'vertical_rate'  => round((float)($state[11] ?? 0), 1),
                'squawk'         => $state[14] ?? null,
            ];
        }

        return $flights;
    }

    /**
     * Provide realistic demo/fallback flight data when OpenSky API is unavailable.
     */
    private function getDemoFlights(): array
    {
        return [
            ['icao24' => '800b01', 'callsign' => 'IGO2146', 'origin_country' => 'India', 'latitude' => 28.5562, 'longitude' => 77.1000, 'altitude' => 10668, 'on_ground' => false, 'velocity' => 231.5, 'heading' => 190, 'vertical_rate' => 0.0, 'squawk' => '5765'],
            ['icao24' => '800b02', 'callsign' => 'IGO322',  'origin_country' => 'India', 'latitude' => 19.0896, 'longitude' => 72.8656, 'altitude' => 350,   'on_ground' => true,  'velocity' => 0.0,   'heading' => 270, 'vertical_rate' => 0.0,  'squawk' => null],
            ['icao24' => '800c15', 'callsign' => 'AIC127',  'origin_country' => 'India', 'latitude' => 22.6544, 'longitude' => 75.3452, 'altitude' => 11278, 'on_ground' => false, 'velocity' => 243.8, 'heading' => 221, 'vertical_rate' => -0.5, 'squawk' => '1234'],
            ['icao24' => '800d22', 'callsign' => 'VTI815',  'origin_country' => 'India', 'latitude' => 12.9716, 'longitude' => 77.5946, 'altitude' => 0,     'on_ground' => true,  'velocity' => 0.0,   'heading' => 90,  'vertical_rate' => 0.0,  'squawk' => null],
            ['icao24' => '800e33', 'callsign' => 'AXB513',  'origin_country' => 'India', 'latitude' => 15.3920, 'longitude' => 73.8787, 'altitude' => 8534,  'on_ground' => false, 'velocity' => 198.2, 'heading' => 160, 'vertical_rate' => -3.2, 'squawk' => '4512'],
            ['icao24' => '800f44', 'callsign' => 'SJY242',  'origin_country' => 'India', 'latitude' => 26.8467, 'longitude' => 80.9462, 'altitude' => 9449,  'on_ground' => false, 'velocity' => 215.0, 'heading' => 145, 'vertical_rate' => 0.0,  'squawk' => '3456'],
            ['icao24' => '800a55', 'callsign' => 'IGO6315', 'origin_country' => 'India', 'latitude' => 17.2403, 'longitude' => 78.4294, 'altitude' => 7320,  'on_ground' => false, 'velocity' => 202.5, 'heading' => 355, 'vertical_rate' => 4.8,  'squawk' => '6012'],
            ['icao24' => 'a12345', 'callsign' => 'UAE512',  'origin_country' => 'United Arab Emirates', 'latitude' => 28.8562, 'longitude' => 77.4561, 'altitude' => 3048,  'on_ground' => false, 'velocity' => 128.6, 'heading' => 270, 'vertical_rate' => -8.5, 'squawk' => '7402'],
            ['icao24' => 'a23456', 'callsign' => 'BAW117',  'origin_country' => 'United Kingdom',       'latitude' => 29.0123, 'longitude' => 76.8901, 'altitude' => 11887, 'on_ground' => false, 'velocity' => 256.2, 'heading' => 120, 'vertical_rate' => 0.0,  'squawk' => '2541'],
            ['icao24' => 'a34567', 'callsign' => 'SIA325',  'origin_country' => 'Singapore',            'latitude' => 27.1956, 'longitude' => 77.8234, 'altitude' => 12192, 'on_ground' => false, 'velocity' => 267.3, 'heading' => 315, 'vertical_rate' => 0.0,  'squawk' => '1342'],
            ['icao24' => 'a45678', 'callsign' => 'QTR579',  'origin_country' => 'Qatar',                'latitude' => 28.2345, 'longitude' => 76.5678, 'altitude' => 10058, 'on_ground' => false, 'velocity' => 238.4, 'heading' => 95,  'vertical_rate' => 2.1,  'squawk' => '6543'],
            ['icao24' => 'a56789', 'callsign' => 'THY714',  'origin_country' => 'Turkey',               'latitude' => 29.5432, 'longitude' => 77.2109, 'altitude' => 11582, 'on_ground' => false, 'velocity' => 249.7, 'heading' => 135, 'vertical_rate' => 0.0,  'squawk' => '4321'],
            ['icao24' => 'a67890', 'callsign' => 'ETD227',  'origin_country' => 'United Arab Emirates', 'latitude' => 27.8765, 'longitude' => 76.4321, 'altitude' => 9753,  'on_ground' => false, 'velocity' => 224.1, 'heading' => 88,  'vertical_rate' => 1.5,  'squawk' => '5678'],
            ['icao24' => '800g66', 'callsign' => 'IGO715',  'origin_country' => 'India', 'latitude' => 22.6520, 'longitude' => 88.4476, 'altitude' => 457,   'on_ground' => false, 'velocity' => 82.3,  'heading' => 198, 'vertical_rate' => -5.2, 'squawk' => '7711'],
            ['icao24' => '800h77', 'callsign' => 'AIC449',  'origin_country' => 'India', 'latitude' => 22.5726, 'longitude' => 88.3639, 'altitude' => 0,     'on_ground' => true,  'velocity' => 0.0,   'heading' => 180, 'vertical_rate' => 0.0,  'squawk' => null],
            ['icao24' => '800i88', 'callsign' => 'IGO269',  'origin_country' => 'India', 'latitude' => 23.1234, 'longitude' => 87.9876, 'altitude' => 6096,  'on_ground' => false, 'velocity' => 195.6, 'heading' => 35,  'vertical_rate' => 7.8,  'squawk' => '3322'],
            ['icao24' => '800j99', 'callsign' => 'AXB223',  'origin_country' => 'India', 'latitude' => 22.3456, 'longitude' => 88.8901, 'altitude' => 8534,  'on_ground' => false, 'velocity' => 210.3, 'heading' => 310, 'vertical_rate' => 0.0,  'squawk' => '4455'],
            ['icao24' => '800k01', 'callsign' => 'IGO462',  'origin_country' => 'India', 'latitude' => 19.0887, 'longitude' => 72.8679, 'altitude' => 305,   'on_ground' => false, 'velocity' => 75.2,  'heading' => 270, 'vertical_rate' => -6.4, 'squawk' => '5511'],
            ['icao24' => '800k02', 'callsign' => 'AIC678',  'origin_country' => 'India', 'latitude' => 19.2345, 'longitude' => 73.1234, 'altitude' => 4572,  'on_ground' => false, 'velocity' => 165.8, 'heading' => 45,  'vertical_rate' => 9.2,  'squawk' => '2233'],
            ['icao24' => '800k03', 'callsign' => 'SJY177',  'origin_country' => 'India', 'latitude' => 18.9012, 'longitude' => 72.7890, 'altitude' => 9144,  'on_ground' => false, 'velocity' => 225.4, 'heading' => 180, 'vertical_rate' => 0.0,  'squawk' => '6677'],
            ['icao24' => '800m01', 'callsign' => 'VTI234',  'origin_country' => 'India', 'latitude' => 13.1986, 'longitude' => 77.7066, 'altitude' => 610,   'on_ground' => false, 'velocity' => 89.5,  'heading' => 90,  'vertical_rate' => -4.6, 'squawk' => '1122'],
            ['icao24' => '800m02', 'callsign' => 'AIC991',  'origin_country' => 'India', 'latitude' => 12.8543, 'longitude' => 77.3456, 'altitude' => 7620,  'on_ground' => false, 'velocity' => 198.7, 'heading' => 350, 'vertical_rate' => 3.1,  'squawk' => '8899'],
            ['icao24' => 'abcde1', 'callsign' => 'AAL100',  'origin_country' => 'United States', 'latitude' => 40.6413, 'longitude' => -73.7781,  'altitude' => 0,     'on_ground' => true,  'velocity' => 0.0,   'heading' => 135, 'vertical_rate' => 0.0,  'squawk' => null],
            ['icao24' => 'abcde2', 'callsign' => 'DAL425',  'origin_country' => 'United States', 'latitude' => 40.7234, 'longitude' => -73.8567,  'altitude' => 2438,  'on_ground' => false, 'velocity' => 112.3, 'heading' => 220, 'vertical_rate' => -7.8, 'squawk' => '3412'],
            ['icao24' => 'abcde3', 'callsign' => 'UAL891',  'origin_country' => 'United States', 'latitude' => 40.5678, 'longitude' => -73.6789,  'altitude' => 10668, 'on_ground' => false, 'velocity' => 245.6, 'heading' => 75,  'vertical_rate' => 0.0,  'squawk' => '5678'],
            ['icao24' => 'bcdef1', 'callsign' => 'BAW456',  'origin_country' => 'United Kingdom', 'latitude' => 51.4700, 'longitude' => -0.4543,   'altitude' => 0,     'on_ground' => true,  'velocity' => 0.0,   'heading' => 270, 'vertical_rate' => 0.0,  'squawk' => null],
            ['icao24' => 'bcdef2', 'callsign' => 'VIR85',   'origin_country' => 'United Kingdom', 'latitude' => 51.5234, 'longitude' => -0.3456,   'altitude' => 3962,  'on_ground' => false, 'velocity' => 142.1, 'heading' => 270, 'vertical_rate' => 5.6,  'squawk' => '7766'],
            ['icao24' => 'cdef12', 'callsign' => 'UAE234',  'origin_country' => 'United Arab Emirates', 'latitude' => 25.2532, 'longitude' => 55.3657, 'altitude' => 0,     'on_ground' => true,  'velocity' => 0.0,   'heading' => 120, 'vertical_rate' => 0.0,  'squawk' => null],
            ['icao24' => 'cdef13', 'callsign' => 'FDB345',  'origin_country' => 'United Arab Emirates', 'latitude' => 25.3456, 'longitude' => 55.4567, 'altitude' => 5182,  'on_ground' => false, 'velocity' => 178.3, 'heading' => 310, 'vertical_rate' => 8.4,  'squawk' => '2211'],
            ['icao24' => 'def123', 'callsign' => 'SIA421',  'origin_country' => 'Singapore', 'latitude' => 1.3644,  'longitude' => 103.9915, 'altitude' => 1524,  'on_ground' => false, 'velocity' => 98.4,  'heading' => 180, 'vertical_rate' => -6.1, 'squawk' => '4400'],
            ['icao24' => 'def124', 'callsign' => 'SIA788',  'origin_country' => 'Singapore', 'latitude' => 1.4567,  'longitude' => 104.0123, 'altitude' => 10363, 'on_ground' => false, 'velocity' => 252.8, 'heading' => 350, 'vertical_rate' => 0.0,  'squawk' => '5566'],
            ['icao24' => '800n01', 'callsign' => 'IGO812',  'origin_country' => 'India', 'latitude' => 17.2403, 'longitude' => 78.4294, 'altitude' => 2134,  'on_ground' => false, 'velocity' => 125.6, 'heading' => 270, 'vertical_rate' => -4.2, 'squawk' => '3344'],
            ['icao24' => '800n02', 'callsign' => 'AIC356',  'origin_country' => 'India', 'latitude' => 17.4567, 'longitude' => 78.6789, 'altitude' => 9753,  'on_ground' => false, 'velocity' => 218.9, 'heading' => 25,  'vertical_rate' => 0.0,  'squawk' => '6655'],
            ['icao24' => 'ef1234', 'callsign' => 'UAL789',  'origin_country' => 'United States', 'latitude' => 33.9416, 'longitude' => -118.4085, 'altitude' => 0,     'on_ground' => true,  'velocity' => 0.0,   'heading' => 250, 'vertical_rate' => 0.0,  'squawk' => null],
            ['icao24' => 'ef1235', 'callsign' => 'SWA456',  'origin_country' => 'United States', 'latitude' => 34.0567, 'longitude' => -118.2345, 'altitude' => 6401,  'on_ground' => false, 'velocity' => 192.4, 'heading' => 300, 'vertical_rate' => 5.3,  'squawk' => '1177'],
        ];
    }
}