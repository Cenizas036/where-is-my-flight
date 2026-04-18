<?php

namespace App\Http\Controllers;

use App\Models\Flight;
use App\Models\FlightWatch;
use App\Services\OpenSkyService;
use App\Services\AircraftDataService;
use App\Services\OtaSearchService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * FlightController — Flight board, profile, search, booking, livecam, and tracking.
 * Powered by real-time OpenSky Network + HexDB data.
 */
class FlightController extends Controller
{
    private array $airportBounds = [
        'JFK' => [40.4, -74.2, 41.0, -73.4],
        'LAX' => [33.5, -118.8, 34.2, -117.8],
        'LHR' => [51.2, -0.8, 51.8, 0.2],
        'DXB' => [24.8, 54.8, 25.6, 55.8],
        'DEL' => [28.2, 76.7, 29.0, 77.5],
        'SIN' => [1.0, 103.5, 1.7, 104.3],
        'ORD' => [41.5, -88.2, 42.2, -87.4],
        'ATL' => [33.3, -84.8, 34.0, -84.0],
        'DFW' => [32.5, -97.5, 33.2, -96.5],
        'CDG' => [48.6, 2.1, 49.3, 2.9],
        'FRA' => [49.8, 8.2, 50.4, 9.0],
        'HND' => [35.3, 139.4, 36.0, 140.2],
        'PEK' => [39.5, 116.0, 40.3, 117.0],
        'BOM' => [18.7, 72.5, 19.4, 73.3],
        'SFO' => [37.3, -122.8, 37.9, -122.0],
        'MIA' => [25.5, -80.7, 26.2, -79.9],
        'BLR' => [12.6, 77.2, 13.3, 78.0],
        'HYD' => [17.0, 78.0, 17.8, 78.8],
        'CCU' => [22.2, 88.0, 22.9, 88.8],
        'MAA' => [12.7, 79.8, 13.4, 80.6],
    ];

    /**
     * Display the live flight board for an airport.
     */
    public function board(Request $request, ?string $airport = null)
    {
        $airportIata = strtoupper($airport ?? $request->get('airport', 'JFK'));
        $boardType = $request->get('type', 'departures');

        $openSky = new OpenSkyService();

        if (isset($this->airportBounds[$airportIata])) {
            [$lamin, $lomin, $lamax, $lomax] = $this->airportBounds[$airportIata];
            $rawFlights = $openSky->getFlightsByBoundingBox($lamin, $lomin, $lamax, $lomax);
        } else {
            $rawFlights = array_slice($openSky->getAllFlights(), 0, 100);
        }

        $flights = array_map(function ($f) {
            $airline = getAirlineFromCallsign($f['callsign']);
            $status = getFlightStatus($f);
            $location = getLocationDescription($f['latitude'], $f['longitude'], $f['on_ground']);
            return array_merge($f, [
                'airline' => $airline,
                'status_info' => $status,
                'location_desc' => $location,
                'speed_kmh' => round($f['velocity'] * 3.6),
            ]);
        }, $rawFlights);

        $airportNames = [
            'JFK' => 'John F. Kennedy International Airport', 'LAX' => 'Los Angeles International Airport',
            'LHR' => 'London Heathrow Airport', 'DXB' => 'Dubai International Airport',
            'DEL' => 'Indira Gandhi International Airport', 'SIN' => 'Singapore Changi Airport',
            'ORD' => "O'Hare International Airport", 'ATL' => 'Hartsfield-Jackson Atlanta International Airport',
            'CDG' => 'Paris Charles de Gaulle Airport', 'FRA' => 'Frankfurt Airport',
            'HND' => 'Tokyo Haneda Airport', 'BOM' => 'Chhatrapati Shivaji Maharaj International Airport',
            'BLR' => 'Kempegowda International Airport', 'HYD' => 'Rajiv Gandhi International Airport',
            'CCU' => 'Netaji Subhas Chandra Bose International Airport', 'MAA' => 'Chennai International Airport',
            'SFO' => 'San Francisco International Airport', 'MIA' => 'Miami International Airport',
        ];

        return view('flights.board', [
            'flights'     => $flights,
            'airport'     => ['name' => $airportNames[$airportIata] ?? $airportIata . ' Airport'],
            'airportIata' => $airportIata,
            'boardType'   => $boardType,
        ]);
    }

    /**
     * ★ Flight Profile Page — Rich detail view with aircraft photo, telemetry, map, booking.
     */
    public function profile(string $callsign)
    {
        $openSky = new OpenSkyService();
        $allFlights = $openSky->getAllFlights();
        $callsignUpper = strtoupper(trim($callsign));

        $flight = null;
        foreach ($allFlights as $f) {
            if (strtoupper(trim($f['callsign'])) === $callsignUpper) {
                $flight = $f;
                break;
            }
        }

        if (!$flight) {
            // Flight not found — could be rate-limited or flight landed
            return redirect()->route('flights.board')->with('success', 
                "Flight {$callsign} is not currently visible in live data. " .
                "It may have landed, or the data source is temporarily unavailable. " .
                "Try searching on the Board or Search page."
            );
        }

        // Enrich data
        $airline = getAirlineFromCallsign($flight['callsign']);
        $status = getFlightStatus($flight);
        $location = getLocationDescription($flight['latitude'], $flight['longitude'], $flight['on_ground']);
        $nearestAirport = getNearestAirport($flight['latitude'], $flight['longitude']);

        // Get aircraft info + photo from HexDB
        $aircraftService = new AircraftDataService();
        $aircraftInfo = $aircraftService->getAircraftInfo($flight['icao24']);
        $aircraftPhoto = $aircraftService->getAircraftPhoto($flight['icao24']);

        // Get weather at flight's location from Open-Meteo (free, no key)
        $weather = $this->getWeather($flight['latitude'], $flight['longitude']);
        
        // Fetch matching database flight for real source/destination and Gate Assignment
        $dbFlight = \App\Models\Flight::where('flight_number', $flight['callsign'])
                        ->with(['departureAirport', 'arrivalAirport'])
                        ->first();

        // Ensure we pass a route array if available from DB
        $route = null;
        if ($dbFlight && $dbFlight->departureAirport && $dbFlight->arrivalAirport) {
            $route = [
                'origin' => ['iata' => $dbFlight->departureAirport->iata_code, 'city' => $dbFlight->departureAirport->city],
                'destination' => ['iata' => $dbFlight->arrivalAirport->iata_code, 'city' => $dbFlight->arrivalAirport->city]
            ];
        } else {
            // Live Lookup from FlightRadar24 free open api (for Live Flights not in DB)
            $callsignTrimmed = trim($flight['callsign']);
            try {
                $fr24 = \Illuminate\Support\Facades\Cache::remember("fr24_route_feed_{$callsignTrimmed}", 3600, function () use ($callsignTrimmed) {
                    return \Illuminate\Support\Facades\Http::withoutVerifying()->timeout(5)
                        ->withHeaders([
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                            'Accept' => 'application/json'
                        ])
                        ->get('https://data-cloud.flightradar24.com/zones/fcgi/feed.js?callsign=' . urlencode($callsignTrimmed))
                        ->json();
                });
                
                if (is_array($fr24)) {
                    foreach ($fr24 as $key => $val) {
                        if ($key !== 'full_count' && $key !== 'version' && is_array($val) && count($val) >= 13) {
                            $orgIata = $val[11] ?? '';
                            $dstIata = $val[12] ?? '';
                            
                            if ($orgIata || $dstIata) {
                                $ota = new \App\Services\OtaSearchService();
                                $orgInfo = $orgIata ? $ota->getAirportInfo($orgIata) : ['city' => 'Unknown'];
                                $dstInfo = $dstIata ? $ota->getAirportInfo($dstIata) : ['city' => 'Unknown'];
                                
                                $route = [
                                    'origin' => ['iata' => $orgIata ?: '?', 'city' => $orgInfo['city'] ?? 'Unknown'],
                                    'destination' => ['iata' => $dstIata ?: '?', 'city' => $dstInfo['city'] ?? 'Unknown']
                                ];
                            }
                            break;
                        }
                    }
                }

                // Secondary fallback if feed.js fails
                if (!$route) {
                    $fr24List = \Illuminate\Support\Facades\Cache::remember("fr24_route_{$callsignTrimmed}", 3600, function () use ($callsignTrimmed) {
                        return \Illuminate\Support\Facades\Http::withoutVerifying()->timeout(5)
                            ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                            ->get('https://api.flightradar24.com/common/v1/flight/list.json?query=' . urlencode($callsignTrimmed) . '&fetchBy=flight&page=1&limit=1')
                            ->json();
                    });
                    
                    if (!empty($fr24List['result']['response']['data'][0]['airport']['origin']) || !empty($fr24List['result']['response']['data'][0]['airport']['destination'])) {
                        $org = $fr24List['result']['response']['data'][0]['airport']['origin'] ?? null;
                        $dst = $fr24List['result']['response']['data'][0]['airport']['destination'] ?? null;
                        $route = [
                            'origin' => ['iata' => $org['code']['iata'] ?? '?', 'city' => $org['position']['region']['city'] ?? $org['name'] ?? 'Unknown'],
                            'destination' => ['iata' => $dst['code']['iata'] ?? '?', 'city' => $dst['position']['region']['city'] ?? $dst['name'] ?? 'Unknown']
                        ];
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('FR24 Route Error: ' . $e->getMessage());
                // silently fail and remain null
            }
        }

        // --- AI Delay Prediction (Heuristic Model) ---
        // Calculates a simulated machine-learning prediction based on flight + weather data
        $prediction = [
            'probability' => 2, // Base 2% probability
            'estimated_min' => 0,
            'cause' => 'None',
            'trend' => 'stable'
        ];

        if ($weather) {
            // Weather factors
            if (str_contains($weather['condition'], 'Rain') || str_contains($weather['condition'], 'Snow')) {
                $prediction['probability'] += 35;
                $prediction['cause'] = 'Adverse weather conditions';
                $prediction['trend'] = 'increasing';
            }
            if ($weather['wind_speed'] > 40) {
                $prediction['probability'] += 20;
                $prediction['cause'] = 'High wind speeds';
            }
            if ($weather['cloud_cover'] > 80) {
                $prediction['probability'] += 10;
            }
        }

        // Flight telemetry factors
        if ($flight['altitude'] < 5000 && !$flight['on_ground'] && $flight['velocity'] < 100) {
            // Unusually low and slow 
            $prediction['probability'] += 15;
            $prediction['cause'] = 'Air traffic congestion';
            $prediction['trend'] = 'increasing';
        }
        
        if ($flight['on_ground'] && $nearestAirport && $nearestAirport['distance_km'] < 5) {
            // Ground delay
            $prediction['probability'] += 25;
            $prediction['cause'] = 'Ground operations backlog';
        }

        // Cap at 98%
        $prediction['probability'] = min(98, $prediction['probability']);
        
        // Calculate estimated minutes based on probability curve
        if ($prediction['probability'] > 40) {
            $baseMin = ($prediction['probability'] - 40) * 1.5;
            $prediction['estimated_min'] = round($baseMin + rand(-10, 20));
            $prediction['estimated_min'] = max(15, $prediction['estimated_min']); // Minimum 15 min if delayed
        }

        // Booking links
        $origin = $nearestAirport ? $nearestAirport['iata'] : '';
        $bookingLinks = getBookingLinks($origin, '', $airline['name'] ?? '');

        return view('flights.profile', [
            'flight'         => $flight,
            'airline'        => $airline,
            'status'         => $status,
            'location'       => $location,
            'nearestAirport' => $nearestAirport,
            'aircraftInfo'   => $aircraftInfo,
            'aircraftPhoto'  => $aircraftPhoto,
            'bookingLinks'   => $bookingLinks,
            'weather'        => $weather,
            'prediction'     => $prediction,
            'dbFlight'      => $dbFlight,
            'route'         => $route,
        ]);
    }

    /**
     * ★ Booking aggregator page.
     */
    public function booking(Request $request, ?string $callsign = null)
    {
        $origin = strtoupper($request->get('from', ''));
        $destination = strtoupper($request->get('to', ''));
        $airlineName = '';

        // If we have a callsign, pre-fill from flight data
        $flight = null;
        if ($callsign) {
            $openSky = new OpenSkyService();
            foreach ($openSky->getAllFlights() as $f) {
                if (strtoupper(trim($f['callsign'])) === strtoupper(trim($callsign))) {
                    $flight = $f;
                    break;
                }
            }

            if ($flight) {
                $airline = getAirlineFromCallsign($flight['callsign']);
                $airlineName = $airline['name'] ?? '';
                $nearest = getNearestAirport($flight['latitude'], $flight['longitude']);
                if ($nearest && empty($origin)) {
                    $origin = $nearest['iata'];
                }
            }
        }

        $bookingLinks = getBookingLinks($origin, $destination, $airlineName);

        return view('flights.booking', [
            'flight'       => $flight,
            'airline'      => $flight ? getAirlineFromCallsign($flight['callsign']) : null,
            'origin'       => $origin,
            'destination'  => $destination,
            'bookingLinks' => $bookingLinks,
        ]);
    }

    /**
     * ★ Live camera feed page.
     */
    public function livecam(Request $request)
    {
        $searchQuery = $request->get('q', '');

        return view('flights.livecam', [
            'searchQuery' => $searchQuery,
        ]);
    }

    /**
     * Search flights from real-time OpenSky data by callsign, airline name, country, or airport.
     */
    public function search(Request $request)
    {
        $query = trim($request->get('q', ''));

        if (empty($query)) {
            return view('flights.search', ['results' => [], 'query' => '', 'airlines' => getPopularAirlines(), 'airportInfo' => null]);
        }

        $openSky = new OpenSkyService();

        // Check if the query is an airport code
        $airportInfo = null;
        $isAirportSearch = false;
        $queryUpper = strtoupper($query);
        $airportCoords = getAirportCoords($queryUpper);

        if ($airportCoords && strlen($query) === 3) {
            $isAirportSearch = true;
            $airportInfo = $airportCoords;
            $airportInfo['iata'] = $queryUpper;

            // Get flights near this airport using bounding box
            $lat = $airportCoords['lat'];
            $lon = $airportCoords['lon'];
            $allFlights = $openSky->getFlightsByBoundingBox($lat - 0.5, $lon - 0.5, $lat + 0.5, $lon + 0.5);

            // Categorize flights
            $results = [];
            foreach ($allFlights as $f) {
                $airline = getAirlineFromCallsign($f['callsign']);
                $status = getFlightStatus($f);
                $dist = haversineDistance($lat, $lon, $f['latitude'], $f['longitude']);
                $results[] = array_merge($f, [
                    'airline' => $airline,
                    'status_info' => $status,
                    'distance_km' => round($dist, 1),
                    'category' => $f['on_ground'] ? 'ground' : ($f['vertical_rate'] < -1 ? 'landing' : ($f['vertical_rate'] > 1 ? 'departing' : 'nearby'))
                ]);
            }

            // Sort: ground first, then landing, then departing, then nearby
            usort($results, function ($a, $b) {
                $order = ['ground' => 0, 'landing' => 1, 'departing' => 2, 'nearby' => 3];
                return ($order[$a['category']] ?? 4) <=> ($order[$b['category']] ?? 4);
            });

            return view('flights.search', [
                'results'     => $results,
                'query'       => $query,
                'airlines'    => getPopularAirlines(),
                'airportInfo' => $airportInfo,
            ]);
        }

        // Standard search — by airline, callsign, or country
        $allFlights = $openSky->getAllFlights();
        $searchUpper = strtoupper($query);

        $results = [];
        $airlineMap = getAllAirlines();

        $matchingPrefixes = [];
        foreach ($airlineMap as $prefix => $info) {
            if (stripos($info['name'], $query) !== false ||
                stripos($prefix, $searchUpper) !== false ||
                (isset($info['iata']) && stripos($info['iata'], $searchUpper) !== false)) {
                $matchingPrefixes[] = $prefix;
            }
        }

        foreach ($allFlights as $f) {
            $callsign = strtoupper(trim($f['callsign']));
            $matched = false;

            if (strpos($callsign, $searchUpper) !== false) $matched = true;
            if (!$matched && stripos($f['origin_country'], $query) !== false) $matched = true;
            if (!$matched) {
                foreach ($matchingPrefixes as $prefix) {
                    if (strpos($callsign, $prefix) === 0) { $matched = true; break; }
                }
            }

            if ($matched) {
                $airline = getAirlineFromCallsign($f['callsign']);
                $status = getFlightStatus($f);
                $locDesc = getLocationDescription($f['latitude'], $f['longitude'], $f['on_ground']);
                $results[] = array_merge($f, ['airline' => $airline, 'status_info' => $status, 'location_desc' => $locDesc]);
            }

            if (count($results) >= 100) break;
        }

        return view('flights.search', [
            'results'     => $results,
            'query'       => $query,
            'airlines'    => getPopularAirlines(),
            'airportInfo' => $airportInfo,
        ]);
    }

    /**
     * ★ User Dashboard — Flights near user's location.
     */
    public function userDashboard(Request $request)
    {
        return view('flights.user_dashboard');
    }

    /**
     * ★ API: Get flights near a lat/lon (for AJAX from user dashboard).
     */
    public function apiNearbyFlights(Request $request): JsonResponse
    {
        $lat = (float) $request->get('lat', 0);
        $lon = (float) $request->get('lon', 0);

        if ($lat == 0 && $lon == 0) {
            return response()->json(['error' => 'No location provided'], 400);
        }

        $openSky = new OpenSkyService();
        $flights = $openSky->getFlightsByBoundingBox($lat - 0.8, $lon - 0.8, $lat + 0.8, $lon + 0.8);

        $nearestAirport = getNearestAirport($lat, $lon);

        $enriched = array_map(function ($f) use ($lat, $lon) {
            $airline = getAirlineFromCallsign($f['callsign']);
            $status = getFlightStatus($f);
            $dist = haversineDistance($lat, $lon, $f['latitude'], $f['longitude']);
            $locDesc = getLocationDescription($f['latitude'], $f['longitude'], $f['on_ground']);
            return array_merge($f, [
                'airline_name' => $airline['name'] ?? '',
                'airline_iata' => $airline['iata'] ?? '',
                'display' => $airline['display'] ?? $f['callsign'],
                'status_text' => $status['status'],
                'status_icon' => $status['icon'],
                'distance_km' => round($dist, 1),
                'speed_kmh' => round($f['velocity'] * 3.6),
                'location_desc' => $locDesc,
            ]);
        }, $flights);

        // Sort by distance
        usort($enriched, fn($a, $b) => $a['distance_km'] <=> $b['distance_km']);

        return response()->json([
            'flights' => array_slice($enriched, 0, 50),
            'nearest_airport' => $nearestAirport,
            'user_lat' => $lat,
            'user_lon' => $lon,
        ]);
    }

    /**
     * ★ API: Get aircraft info by ICAO24 (for AJAX from profile page).
     */
    public function apiAircraftInfo(string $icao24): JsonResponse
    {
        $service = new AircraftDataService();
        return response()->json($service->getFullProfile($icao24));
    }

    // ─── Existing methods ───

    public function show(string $flightNumber, ?string $date = null)
    {
        return redirect()->route('flight.profile', ['callsign' => $flightNumber]);
    }

    public function watch(Request $request, Flight $flight)
    {
        FlightWatch::updateOrCreate(
            ['user_id' => auth()->id(), 'flight_id' => $flight->id],
            [
                'notify_gate_change' => $request->boolean('notify_gate', true),
                'notify_delay'       => $request->boolean('notify_delay', true),
                'notify_status'      => $request->boolean('notify_status', true),
            ]
        );
        return back()->with('success', "Now tracking {$flight->flight_number}");
    }

    public function unwatch(Flight $flight)
    {
        FlightWatch::where('user_id', auth()->id())->where('flight_id', $flight->id)->delete();
        return back()->with('success', "Stopped tracking {$flight->flight_number}");
    }

    public function myFlights()
    {
        $watches = FlightWatch::where('user_id', auth()->id())
            ->with('flight')->orderBy('created_at', 'desc')->paginate(20);
        return view('flights.mine', ['watches' => $watches]);
    }

    /**
     * ★ API: Get actual flights between two airports for booking page.
     */
    public function apiRouteFlights(Request $request): JsonResponse
    {
        $from = strtoupper($request->get('from', ''));
        $to = strtoupper($request->get('to', ''));

        if (empty($from)) {
            return response()->json(['flights' => [], 'error' => 'Origin required']);
        }

        $openSky = new OpenSkyService();
        $fromCoords = getAirportCoords($from);
        $toCoords = $to ? getAirportCoords($to) : null;

        $flights = [];

        if ($fromCoords) {
            $lat = $fromCoords['lat'];
            $lon = $fromCoords['lon'];
            $nearby = $openSky->getFlightsByBoundingBox($lat - 1.0, $lon - 1.0, $lat + 1.0, $lon + 1.0);

            foreach ($nearby as $f) {
                $airline = getAirlineFromCallsign($f['callsign']);
                $status = getFlightStatus($f);
                $locDesc = getLocationDescription($f['latitude'], $f['longitude'], $f['on_ground']);

                // If destination specified, filter by heading toward it
                $include = true;
                if ($toCoords && !$f['on_ground']) {
                    $bearing = $this->calculateBearing($f['latitude'], $f['longitude'], $toCoords['lat'], $toCoords['lon']);
                    $headingDiff = abs($f['heading'] - $bearing);
                    if ($headingDiff > 180) $headingDiff = 360 - $headingDiff;
                    $include = $headingDiff < 60; // heading roughly toward destination
                }

                if ($include) {
                    $flights[] = [
                        'callsign' => trim($f['callsign']),
                        'airline_name' => $airline['name'] ?? '',
                        'airline_iata' => $airline['iata'] ?? '',
                        'display' => $airline['display'] ?? $f['callsign'],
                        'altitude' => $f['altitude'],
                        'speed_kmh' => round($f['velocity'] * 3.6),
                        'heading' => $f['heading'],
                        'status_text' => $status['status'],
                        'status_icon' => $status['icon'],
                        'on_ground' => $f['on_ground'],
                        'latitude' => $f['latitude'],
                        'longitude' => $f['longitude'],
                        'location_desc' => $locDesc,
                        'origin_country' => $f['origin_country'],
                    ];
                }
            }
        }

        return response()->json([
            'flights' => array_slice($flights, 0, 30),
            'from' => $fromCoords,
            'to' => $toCoords,
        ]);
    }

    /**
     * OTA Search — proxy to OtaSearchService for mock bookable flight results.
     */
    public function otaSearch(Request $request): JsonResponse
    {
        $svc    = new OtaSearchService();
        $from   = $request->get('from', '');
        $to     = $request->get('to', '');
        $date   = $request->get('date', now()->addDays(7)->format('Y-m-d'));
        $cls    = $request->get('class', 'economy');
        $sortBy = $request->get('sort', 'price'); // price|duration|departure

        if (strlen($from) < 3 || strlen($to) < 3) {
            return response()->json(['error' => 'Enter valid 3-letter airport codes', 'flights' => []], 422);
        }

        $result = $svc->search($from, $to, $date, $cls);

        if ($sortBy === 'departure') {
            usort($result['flights'], fn($a, $b) => strcmp($a['departure'], $b['departure']));
        } elseif ($sortBy === 'duration') {
            usort($result['flights'], fn($a, $b) => strcmp($a['duration'], $b['duration']));
        }

        return response()->json($result);
    }

    /**
     * Calculate bearing between two points.
     */
    private function calculateBearing(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $dLon = deg2rad($lon2 - $lon1);
        $y = sin($dLon) * cos(deg2rad($lat2));
        $x = cos(deg2rad($lat1)) * sin(deg2rad($lat2)) - sin(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos($dLon);
        $bearing = rad2deg(atan2($y, $x));
        return fmod($bearing + 360, 360);
    }

    /**
     * Get weather at a lat/lon from Open-Meteo (free, no API key needed).
     * Cached for 10 minutes.
     */
    private function getWeather(float $lat, float $lon): ?array
    {
        $cacheKey = 'weather:' . round($lat, 1) . ':' . round($lon, 1);

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 600, function () use ($lat, $lon) {
            try {
                $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                    ->timeout(5)
                    ->get('https://api.open-meteo.com/v1/forecast', [
                        'latitude' => $lat,
                        'longitude' => $lon,
                        'current' => 'temperature_2m,weather_code,wind_speed_10m,wind_direction_10m,cloud_cover,relative_humidity_2m',
                        'timezone' => 'auto',
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $current = $data['current'] ?? [];
                    $wmoCode = $current['weather_code'] ?? 0;

                    return [
                        'temp' => $current['temperature_2m'] ?? null,
                        'wind_speed' => $current['wind_speed_10m'] ?? null,
                        'wind_dir' => $current['wind_direction_10m'] ?? null,
                        'cloud_cover' => $current['cloud_cover'] ?? null,
                        'humidity' => $current['relative_humidity_2m'] ?? null,
                        'condition' => $this->wmoToCondition($wmoCode),
                        'icon' => $this->wmoToIcon($wmoCode),
                    ];
                }
                return null;
            } catch (\Exception $e) {
                return null;
            }
        });
    }

    private function wmoToCondition(int $code): string
    {
        return match(true) {
            $code === 0 => 'Clear Sky',
            $code <= 3 => 'Partly Cloudy',
            in_array($code, [45, 48]) => 'Foggy',
            in_array($code, [51, 53, 55]) => 'Drizzle',
            in_array($code, [61, 63, 65]) => 'Rainy',
            in_array($code, [66, 67]) => 'Freezing Rain',
            in_array($code, [71, 73, 75, 77]) => 'Snowy',
            in_array($code, [80, 81, 82]) => 'Rain Showers',
            in_array($code, [85, 86]) => 'Snow Showers',
            in_array($code, [95, 96, 99]) => 'Thunderstorm',
            default => 'Unknown',
        };
    }

    private function wmoToIcon(int $code): string
    {
        return match(true) {
            $code === 0 => '☀️',
            $code <= 3 => '⛅',
            in_array($code, [45, 48]) => '🌫️',
            in_array($code, [51, 53, 55]) => '🌦️',
            in_array($code, [61, 63, 65]) => '🌧️',
            in_array($code, [66, 67]) => '🧊',
            in_array($code, [71, 73, 75, 77]) => '❄️',
            in_array($code, [80, 81, 82]) => '🌧️',
            in_array($code, [85, 86]) => '🌨️',
            in_array($code, [95, 96, 99]) => '⛈️',
            default => '🌤️',
        };
    }
}

