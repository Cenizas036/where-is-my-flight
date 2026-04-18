<?php

/**
 * Country name to flag emoji helper.
 */
if (!function_exists('getCountryFlag')) {
    function getCountryFlag(string $country): string
    {
        $flags = [
            'United States'  => '🇺🇸', 'United Kingdom' => '🇬🇧', 'Germany'        => '🇩🇪',
            'France'         => '🇫🇷', 'China'          => '🇨🇳', 'Japan'          => '🇯🇵',
            'India'          => '🇮🇳', 'Canada'         => '🇨🇦', 'Australia'      => '🇦🇺',
            'Brazil'         => '🇧🇷', 'Russia'         => '🇷🇺', 'South Korea'    => '🇰🇷',
            'Italy'          => '🇮🇹', 'Spain'          => '🇪🇸', 'Netherlands'    => '🇳🇱',
            'Turkey'         => '🇹🇷', 'Türkiye'        => '🇹🇷', 'Singapore'      => '🇸🇬',
            'Saudi Arabia'   => '🇸🇦', 'Mexico'         => '🇲🇽', 'Indonesia'      => '🇮🇩',
            'Thailand'       => '🇹🇭', 'Switzerland'    => '🇨🇭', 'Sweden'         => '🇸🇪',
            'Norway'         => '🇳🇴', 'Denmark'        => '🇩🇰', 'Ireland'        => '🇮🇪',
            'Portugal'       => '🇵🇹', 'Poland'         => '🇵🇱', 'Austria'        => '🇦🇹',
            'Belgium'        => '🇧🇪', 'Finland'        => '🇫🇮', 'Czech Republic' => '🇨🇿',
            'Czechia'        => '🇨🇿', 'Israel'         => '🇮🇱', 'South Africa'   => '🇿🇦',
            'Argentina'      => '🇦🇷', 'Chile'          => '🇨🇱', 'Colombia'       => '🇨🇴',
            'Malaysia'       => '🇲🇾', 'Philippines'    => '🇵🇭', 'Vietnam'        => '🇻🇳',
            'New Zealand'    => '🇳🇿', 'Taiwan'         => '🇹🇼', 'Hong Kong'      => '🇭🇰',
            'Pakistan'       => '🇵🇰', 'Bangladesh'     => '🇧🇩', 'Egypt'          => '🇪🇬',
            'Qatar'          => '🇶🇦', 'Kuwait'         => '🇰🇼', 'Ethiopia'       => '🇪🇹',
            'Kenya'          => '🇰🇪', 'Nigeria'        => '🇳🇬', 'Morocco'        => '🇲🇦',
            'United Arab Emirates' => '🇦🇪', 'Greece'   => '🇬🇷', 'Romania'        => '🇷🇴',
            'Ukraine'        => '🇺🇦', 'Hungary'        => '🇭🇺', 'Croatia'        => '🇭🇷',
            'Luxembourg'     => '🇱🇺', 'Iceland'        => '🇮🇸', 'Peru'           => '🇵🇪',
            'Venezuela'      => '🇻🇪', 'Cuba'           => '🇨🇺', 'Dominican Republic' => '🇩🇴',
            'Panama'         => '🇵🇦', 'Costa Rica'     => '🇨🇷', 'Jamaica'        => '🇯🇲',
            'Sri Lanka'      => '🇱🇰', 'Nepal'          => '🇳🇵', 'Myanmar'        => '🇲🇲',
            'Cambodia'       => '🇰🇭', 'Laos'           => '🇱🇦',
        ];

        return $flags[$country] ?? '🌍';
    }
}

/**
 * Get all airline ICAO prefix -> info mapping.
 * These are the ICAO 3-letter callsign prefixes used in ADS-B.
 * e.g. "IGO123" means IndiGo flight 123, prefix = "IGO"
 */
if (!function_exists('getAllAirlines')) {
    function getAllAirlines(): array
    {
        return [
            // ── Indian Airlines ──
            'IGO' => ['name' => 'IndiGo', 'iata' => '6E', 'country' => 'India'],
            'AIC' => ['name' => 'Air India', 'iata' => 'AI', 'country' => 'India'],
            'VTI' => ['name' => 'Vistara', 'iata' => 'UK', 'country' => 'India'],
            'AKJ' => ['name' => 'Akasa Air', 'iata' => 'QP', 'country' => 'India'],
            'SEJ' => ['name' => 'SpiceJet', 'iata' => 'SG', 'country' => 'India'],
            'GOW' => ['name' => 'Go First', 'iata' => 'G8', 'country' => 'India'],
            'ALW' => ['name' => 'Alliance Air', 'iata' => '9I', 'country' => 'India'],
            'AIX' => ['name' => 'Air India Express', 'iata' => 'IX', 'country' => 'India'],

            // ── US Airlines ──
            'AAL' => ['name' => 'American Airlines', 'iata' => 'AA', 'country' => 'United States'],
            'DAL' => ['name' => 'Delta Air Lines', 'iata' => 'DL', 'country' => 'United States'],
            'UAL' => ['name' => 'United Airlines', 'iata' => 'UA', 'country' => 'United States'],
            'SWA' => ['name' => 'Southwest Airlines', 'iata' => 'WN', 'country' => 'United States'],
            'JBU' => ['name' => 'JetBlue Airways', 'iata' => 'B6', 'country' => 'United States'],
            'NKS' => ['name' => 'Spirit Airlines', 'iata' => 'NK', 'country' => 'United States'],
            'FFT' => ['name' => 'Frontier Airlines', 'iata' => 'F9', 'country' => 'United States'],
            'ASA' => ['name' => 'Alaska Airlines', 'iata' => 'AS', 'country' => 'United States'],
            'HAL' => ['name' => 'Hawaiian Airlines', 'iata' => 'HA', 'country' => 'United States'],
            'SKW' => ['name' => 'SkyWest Airlines', 'iata' => 'OO', 'country' => 'United States'],
            'RPA' => ['name' => 'Republic Airways', 'iata' => 'YX', 'country' => 'United States'],
            'ENY' => ['name' => 'Envoy Air', 'iata' => 'MQ', 'country' => 'United States'],
            'PDT' => ['name' => 'Piedmont Airlines', 'iata' => 'PT', 'country' => 'United States'],
            'EDV' => ['name' => 'Endeavor Air', 'iata' => '9E', 'country' => 'United States'],
            'FDX' => ['name' => 'FedEx Express', 'iata' => 'FX', 'country' => 'United States'],
            'UPS' => ['name' => 'UPS Airlines', 'iata' => '5X', 'country' => 'United States'],

            // ── European Airlines ──
            'BAW' => ['name' => 'British Airways', 'iata' => 'BA', 'country' => 'United Kingdom'],
            'EZY' => ['name' => 'easyJet', 'iata' => 'U2', 'country' => 'United Kingdom'],
            'VIR' => ['name' => 'Virgin Atlantic', 'iata' => 'VS', 'country' => 'United Kingdom'],
            'AFR' => ['name' => 'Air France', 'iata' => 'AF', 'country' => 'France'],
            'DLH' => ['name' => 'Lufthansa', 'iata' => 'LH', 'country' => 'Germany'],
            'EWG' => ['name' => 'Eurowings', 'iata' => 'EW', 'country' => 'Germany'],
            'KLM' => ['name' => 'KLM Royal Dutch Airlines', 'iata' => 'KL', 'country' => 'Netherlands'],
            'SAS' => ['name' => 'Scandinavian Airlines', 'iata' => 'SK', 'country' => 'Sweden'],
            'AUA' => ['name' => 'Austrian Airlines', 'iata' => 'OS', 'country' => 'Austria'],
            'SWR' => ['name' => 'Swiss International', 'iata' => 'LX', 'country' => 'Switzerland'],
            'IBE' => ['name' => 'Iberia', 'iata' => 'IB', 'country' => 'Spain'],
            'VLG' => ['name' => 'Vueling Airlines', 'iata' => 'VY', 'country' => 'Spain'],
            'AZA' => ['name' => 'ITA Airways', 'iata' => 'AZ', 'country' => 'Italy'],
            'RYR' => ['name' => 'Ryanair', 'iata' => 'FR', 'country' => 'Ireland'],
            'TAP' => ['name' => 'TAP Air Portugal', 'iata' => 'TP', 'country' => 'Portugal'],
            'LOT' => ['name' => 'LOT Polish Airlines', 'iata' => 'LO', 'country' => 'Poland'],
            'FIN' => ['name' => 'Finnair', 'iata' => 'AY', 'country' => 'Finland'],
            'THY' => ['name' => 'Turkish Airlines', 'iata' => 'TK', 'country' => 'Turkey'],
            'WZZ' => ['name' => 'Wizz Air', 'iata' => 'W6', 'country' => 'Hungary'],
            'NOZ' => ['name' => 'Norwegian Air', 'iata' => 'DY', 'country' => 'Norway'],
            'ICE' => ['name' => 'Icelandair', 'iata' => 'FI', 'country' => 'Iceland'],
            'BEL' => ['name' => 'Brussels Airlines', 'iata' => 'SN', 'country' => 'Belgium'],

            // ── Middle Eastern Airlines ──
            'UAE' => ['name' => 'Emirates', 'iata' => 'EK', 'country' => 'United Arab Emirates'],
            'QTR' => ['name' => 'Qatar Airways', 'iata' => 'QR', 'country' => 'Qatar'],
            'ETD' => ['name' => 'Etihad Airways', 'iata' => 'EY', 'country' => 'United Arab Emirates'],
            'SVA' => ['name' => 'Saudia', 'iata' => 'SV', 'country' => 'Saudi Arabia'],
            'FDB' => ['name' => 'flydubai', 'iata' => 'FZ', 'country' => 'United Arab Emirates'],
            'GFA' => ['name' => 'Gulf Air', 'iata' => 'GF', 'country' => 'Bahrain'],
            'OMA' => ['name' => 'Oman Air', 'iata' => 'WY', 'country' => 'Oman'],
            'KAC' => ['name' => 'Kuwait Airways', 'iata' => 'KU', 'country' => 'Kuwait'],
            'ELY' => ['name' => 'El Al', 'iata' => 'LY', 'country' => 'Israel'],
            'RJA' => ['name' => 'Royal Jordanian', 'iata' => 'RJ', 'country' => 'Jordan'],

            // ── Asian / Pacific Airlines ──
            'CPA' => ['name' => 'Cathay Pacific', 'iata' => 'CX', 'country' => 'Hong Kong'],
            'SIA' => ['name' => 'Singapore Airlines', 'iata' => 'SQ', 'country' => 'Singapore'],
            'THA' => ['name' => 'Thai Airways', 'iata' => 'TG', 'country' => 'Thailand'],
            'MAS' => ['name' => 'Malaysia Airlines', 'iata' => 'MH', 'country' => 'Malaysia'],
            'ANA' => ['name' => 'All Nippon Airways', 'iata' => 'NH', 'country' => 'Japan'],
            'JAL' => ['name' => 'Japan Airlines', 'iata' => 'JL', 'country' => 'Japan'],
            'KAL' => ['name' => 'Korean Air', 'iata' => 'KE', 'country' => 'South Korea'],
            'AAR' => ['name' => 'Asiana Airlines', 'iata' => 'OZ', 'country' => 'South Korea'],
            'CCA' => ['name' => 'Air China', 'iata' => 'CA', 'country' => 'China'],
            'CES' => ['name' => 'China Eastern Airlines', 'iata' => 'MU', 'country' => 'China'],
            'CSN' => ['name' => 'China Southern Airlines', 'iata' => 'CZ', 'country' => 'China'],
            'HDA' => ['name' => 'Hainan Airlines', 'iata' => 'HU', 'country' => 'China'],
            'EVA' => ['name' => 'EVA Air', 'iata' => 'BR', 'country' => 'Taiwan'],
            'GIA' => ['name' => 'Garuda Indonesia', 'iata' => 'GA', 'country' => 'Indonesia'],
            'VJC' => ['name' => 'VietJet Air', 'iata' => 'VJ', 'country' => 'Vietnam'],
            'HVN' => ['name' => 'Vietnam Airlines', 'iata' => 'VN', 'country' => 'Vietnam'],
            'PAL' => ['name' => 'Philippine Airlines', 'iata' => 'PR', 'country' => 'Philippines'],
            'QFA' => ['name' => 'Qantas', 'iata' => 'QF', 'country' => 'Australia'],
            'ANZ' => ['name' => 'Air New Zealand', 'iata' => 'NZ', 'country' => 'New Zealand'],
            'AXM' => ['name' => 'AirAsia', 'iata' => 'AK', 'country' => 'Malaysia'],
            'JST' => ['name' => 'Jetstar', 'iata' => 'JQ', 'country' => 'Australia'],
            'PIA' => ['name' => 'Pakistan International', 'iata' => 'PK', 'country' => 'Pakistan'],
            'ALK' => ['name' => 'SriLankan Airlines', 'iata' => 'UL', 'country' => 'Sri Lanka'],
            'BGB' => ['name' => 'Biman Bangladesh', 'iata' => 'BG', 'country' => 'Bangladesh'],

            // ── Americas ──
            'ACA' => ['name' => 'Air Canada', 'iata' => 'AC', 'country' => 'Canada'],
            'WJA' => ['name' => 'WestJet', 'iata' => 'WS', 'country' => 'Canada'],
            'TAM' => ['name' => 'LATAM Brasil', 'iata' => 'JJ', 'country' => 'Brazil'],
            'GLO' => ['name' => 'GOL Airlines', 'iata' => 'G3', 'country' => 'Brazil'],
            'AZU' => ['name' => 'Azul Brazilian Airlines', 'iata' => 'AD', 'country' => 'Brazil'],
            'AMX' => ['name' => 'Aeroméxico', 'iata' => 'AM', 'country' => 'Mexico'],
            'VOI' => ['name' => 'Volaris', 'iata' => 'Y4', 'country' => 'Mexico'],
            'AVA' => ['name' => 'Avianca', 'iata' => 'AV', 'country' => 'Colombia'],
            'LAN' => ['name' => 'LATAM Airlines', 'iata' => 'LA', 'country' => 'Chile'],
            'CMP' => ['name' => 'Copa Airlines', 'iata' => 'CM', 'country' => 'Panama'],

            // ── African Airlines ──
            'ETH' => ['name' => 'Ethiopian Airlines', 'iata' => 'ET', 'country' => 'Ethiopia'],
            'SAA' => ['name' => 'South African Airways', 'iata' => 'SA', 'country' => 'South Africa'],
            'KQA' => ['name' => 'Kenya Airways', 'iata' => 'KQ', 'country' => 'Kenya'],
            'RAM' => ['name' => 'Royal Air Maroc', 'iata' => 'AT', 'country' => 'Morocco'],
            'MSR' => ['name' => 'EgyptAir', 'iata' => 'MS', 'country' => 'Egypt'],
        ];
    }
}

/**
 * Parse a callsign to extract airline info.
 * Callsigns typically start with a 3-letter ICAO prefix.
 */
if (!function_exists('getAirlineFromCallsign')) {
    function getAirlineFromCallsign(string $callsign): array
    {
        $callsign = strtoupper(trim($callsign));
        $airlines = getAllAirlines();

        // Try 3-letter prefix first (most common)
        if (strlen($callsign) >= 3) {
            $prefix3 = substr($callsign, 0, 3);
            if (isset($airlines[$prefix3])) {
                $flightNum = substr($callsign, 3);
                return array_merge($airlines[$prefix3], [
                    'prefix' => $prefix3,
                    'flight_num' => $flightNum,
                    'display' => $airlines[$prefix3]['iata'] . $flightNum,
                ]);
            }
        }

        return [
            'name' => '',
            'iata' => '',
            'country' => '',
            'prefix' => '',
            'flight_num' => $callsign,
            'display' => $callsign,
        ];
    }
}

/**
 * Popular airlines for search suggestions.
 */
if (!function_exists('getPopularAirlines')) {
    function getPopularAirlines(): array
    {
        return [
            ['name' => 'IndiGo', 'prefix' => 'IGO', 'iata' => '6E', 'country' => '🇮🇳'],
            ['name' => 'Air India', 'prefix' => 'AIC', 'iata' => 'AI', 'country' => '🇮🇳'],
            ['name' => 'Emirates', 'prefix' => 'UAE', 'iata' => 'EK', 'country' => '🇦🇪'],
            ['name' => 'American Airlines', 'prefix' => 'AAL', 'iata' => 'AA', 'country' => '🇺🇸'],
            ['name' => 'Delta Air Lines', 'prefix' => 'DAL', 'iata' => 'DL', 'country' => '🇺🇸'],
            ['name' => 'United Airlines', 'prefix' => 'UAL', 'iata' => 'UA', 'country' => '🇺🇸'],
            ['name' => 'British Airways', 'prefix' => 'BAW', 'iata' => 'BA', 'country' => '🇬🇧'],
            ['name' => 'Lufthansa', 'prefix' => 'DLH', 'iata' => 'LH', 'country' => '🇩🇪'],
            ['name' => 'Qatar Airways', 'prefix' => 'QTR', 'iata' => 'QR', 'country' => '🇶🇦'],
            ['name' => 'Singapore Airlines', 'prefix' => 'SIA', 'iata' => 'SQ', 'country' => '🇸🇬'],
            ['name' => 'Turkish Airlines', 'prefix' => 'THY', 'iata' => 'TK', 'country' => '🇹🇷'],
            ['name' => 'Ryanair', 'prefix' => 'RYR', 'iata' => 'FR', 'country' => '🇮🇪'],
        ];
    }
}

/**
 * Airport coordinates database [lat, lon, name, city, country].
 */
if (!function_exists('getAirportDatabase')) {
    function getAirportDatabase(): array
    {
        return [
            'JFK' => [40.6413, -73.7781, 'John F. Kennedy International', 'New York', 'United States'],
            'LAX' => [33.9425, -118.4081, 'Los Angeles International', 'Los Angeles', 'United States'],
            'ORD' => [41.9742, -87.9073, "O'Hare International", 'Chicago', 'United States'],
            'ATL' => [33.6407, -84.4277, 'Hartsfield-Jackson Atlanta International', 'Atlanta', 'United States'],
            'DFW' => [32.8998, -97.0403, 'Dallas/Fort Worth International', 'Dallas', 'United States'],
            'SFO' => [37.6213, -122.3790, 'San Francisco International', 'San Francisco', 'United States'],
            'MIA' => [25.7959, -80.2870, 'Miami International', 'Miami', 'United States'],
            'DEN' => [39.8561, -104.6737, 'Denver International', 'Denver', 'United States'],
            'SEA' => [47.4502, -122.3088, 'Seattle-Tacoma International', 'Seattle', 'United States'],
            'BOS' => [42.3656, -71.0096, 'Boston Logan International', 'Boston', 'United States'],
            'LHR' => [51.4700, -0.4543, 'London Heathrow', 'London', 'United Kingdom'],
            'LGW' => [51.1537, -0.1821, 'London Gatwick', 'London', 'United Kingdom'],
            'CDG' => [49.0097, 2.5479, 'Charles de Gaulle', 'Paris', 'France'],
            'FRA' => [50.0379, 8.5622, 'Frankfurt Airport', 'Frankfurt', 'Germany'],
            'AMS' => [52.3105, 4.7683, 'Amsterdam Schiphol', 'Amsterdam', 'Netherlands'],
            'MAD' => [40.4983, -3.5676, 'Madrid-Barajas', 'Madrid', 'Spain'],
            'BCN' => [41.2971, 2.0785, 'Barcelona El Prat', 'Barcelona', 'Spain'],
            'FCO' => [41.8003, 12.2389, 'Rome Fiumicino', 'Rome', 'Italy'],
            'IST' => [41.2753, 28.7519, 'Istanbul Airport', 'Istanbul', 'Turkey'],
            'ZRH' => [47.4647, 8.5492, 'Zurich Airport', 'Zurich', 'Switzerland'],
            'MUC' => [48.3537, 11.7750, 'Munich Airport', 'Munich', 'Germany'],
            'DXB' => [25.2532, 55.3657, 'Dubai International', 'Dubai', 'United Arab Emirates'],
            'AUH' => [24.4330, 54.6511, 'Abu Dhabi International', 'Abu Dhabi', 'United Arab Emirates'],
            'DOH' => [25.2731, 51.6081, 'Hamad International', 'Doha', 'Qatar'],
            'RUH' => [24.9576, 46.6988, 'King Khalid International', 'Riyadh', 'Saudi Arabia'],
            'DEL' => [28.5562, 77.1000, 'Indira Gandhi International', 'New Delhi', 'India'],
            'BOM' => [19.0896, 72.8656, 'Chhatrapati Shivaji Maharaj International', 'Mumbai', 'India'],
            'BLR' => [13.1986, 77.7066, 'Kempegowda International', 'Bengaluru', 'India'],
            'MAA' => [12.9941, 80.1709, 'Chennai International', 'Chennai', 'India'],
            'HYD' => [17.2403, 78.4294, 'Rajiv Gandhi International', 'Hyderabad', 'India'],
            'CCU' => [22.6520, 88.4463, 'Netaji Subhas Chandra Bose International', 'Kolkata', 'India'],
            'COK' => [10.1520, 76.4019, 'Cochin International', 'Kochi', 'India'],
            'GOI' => [15.3808, 73.8314, 'Goa International', 'Goa', 'India'],
            'SIN' => [1.3644, 103.9915, 'Singapore Changi', 'Singapore', 'Singapore'],
            'HND' => [35.5494, 139.7798, 'Tokyo Haneda', 'Tokyo', 'Japan'],
            'NRT' => [35.7720, 140.3929, 'Tokyo Narita', 'Tokyo', 'Japan'],
            'ICN' => [37.4602, 126.4407, 'Incheon International', 'Seoul', 'South Korea'],
            'PEK' => [40.0799, 116.6031, 'Beijing Capital International', 'Beijing', 'China'],
            'PVG' => [31.1443, 121.8083, 'Shanghai Pudong International', 'Shanghai', 'China'],
            'HKG' => [22.3080, 113.9185, 'Hong Kong International', 'Hong Kong', 'Hong Kong'],
            'BKK' => [13.6900, 100.7501, 'Suvarnabhumi', 'Bangkok', 'Thailand'],
            'KUL' => [2.7456, 101.7099, 'Kuala Lumpur International', 'Kuala Lumpur', 'Malaysia'],
            'SYD' => [-33.9461, 151.1772, 'Sydney Kingsford Smith', 'Sydney', 'Australia'],
            'MEL' => [-37.6733, 144.8433, 'Melbourne Airport', 'Melbourne', 'Australia'],
            'GRU' => [-23.4356, -46.4731, 'São Paulo/Guarulhos', 'São Paulo', 'Brazil'],
            'MEX' => [19.4363, -99.0721, 'Mexico City International', 'Mexico City', 'Mexico'],
            'YYZ' => [43.6772, -79.6306, 'Toronto Pearson International', 'Toronto', 'Canada'],
            'YVR' => [49.1967, -123.1815, 'Vancouver International', 'Vancouver', 'Canada'],
            'JNB' => [-26.1392, 28.2460, 'OR Tambo International', 'Johannesburg', 'South Africa'],
            'CAI' => [30.1219, 31.4056, 'Cairo International', 'Cairo', 'Egypt'],
            'ADD' => [8.9779, 38.7993, 'Addis Ababa Bole International', 'Addis Ababa', 'Ethiopia'],
            'NBO' => [-1.3192, 36.9278, 'Jomo Kenyatta International', 'Nairobi', 'Kenya'],
        ];
    }
}

/**
 * Get nearest airport to a lat/lon coordinate.
 * Returns: ['iata' => ..., 'name' => ..., 'city' => ..., 'distance_km' => ...]
 */
if (!function_exists('getNearestAirport')) {
    function getNearestAirport(float $lat, float $lon): ?array
    {
        $airports = getAirportDatabase();
        $nearest = null;
        $minDist = PHP_FLOAT_MAX;

        foreach ($airports as $iata => [$aLat, $aLon, $name, $city, $country]) {
            $dist = haversineDistance($lat, $lon, $aLat, $aLon);
            if ($dist < $minDist) {
                $minDist = $dist;
                $nearest = [
                    'iata'        => $iata,
                    'name'        => $name,
                    'city'        => $city,
                    'country'     => $country,
                    'lat'         => $aLat,
                    'lon'         => $aLon,
                    'distance_km' => round($dist, 1),
                ];
            }
        }

        return $nearest;
    }
}

/**
 * Haversine formula — distance between two lat/lon points in km.
 */
if (!function_exists('haversineDistance')) {
    function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $R = 6371; // Earth radius in km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $R * $c;
    }
}

/**
 * Get human-readable location description from lat/lon.
 */
if (!function_exists('getLocationDescription')) {
    function getLocationDescription(float $lat, float $lon, bool $onGround = false): string
    {
        $nearest = getNearestAirport($lat, $lon);
        if (!$nearest) return "Unknown location";

        if ($onGround && $nearest['distance_km'] < 5) {
            return "On ground at {$nearest['iata']} ({$nearest['city']})";
        } elseif ($nearest['distance_km'] < 30) {
            return "Near {$nearest['city']}, {$nearest['country']}";
        } else {
            return "Flying over {$nearest['city']}, {$nearest['country']}";
        }
    }
}

/**
 * Determine rich flight status from telemetry data.
 */
if (!function_exists('getFlightStatus')) {
    function getFlightStatus(array $flight): array
    {
        $alt = $flight['altitude'] ?? 0;
        $vr = $flight['vertical_rate'] ?? 0;
        $speed = ($flight['velocity'] ?? 0) * 3.6; // km/h
        $onGround = $flight['on_ground'] ?? false;

        if ($onGround && $speed < 30) {
            return ['status' => 'Parked', 'color' => 'text-gray-400', 'bg' => 'bg-gray-500/20', 'icon' => '🅿️', 'desc' => 'Aircraft is parked at gate'];
        }
        if ($onGround && $speed >= 30) {
            return ['status' => 'Taxiing', 'color' => 'text-amber-400', 'bg' => 'bg-amber-500/20', 'icon' => '🛞', 'desc' => 'Aircraft is taxiing on the runway'];
        }
        if ($alt < 1000 && $vr > 2) {
            return ['status' => 'Taking Off', 'color' => 'text-emerald-400', 'bg' => 'bg-emerald-500/20', 'icon' => '🛫', 'desc' => 'Aircraft is taking off'];
        }
        if ($alt < 3000 && $vr > 1) {
            return ['status' => 'Climbing', 'color' => 'text-blue-400', 'bg' => 'bg-blue-500/20', 'icon' => '📈', 'desc' => 'Aircraft is climbing after departure'];
        }
        if ($alt < 3000 && $vr < -1) {
            return ['status' => 'Landing', 'color' => 'text-orange-400', 'bg' => 'bg-orange-500/20', 'icon' => '🛬', 'desc' => 'Aircraft on final approach'];
        }
        if ($vr < -3 && $alt < 8000) {
            return ['status' => 'Descending', 'color' => 'text-yellow-400', 'bg' => 'bg-yellow-500/20', 'icon' => '📉', 'desc' => 'Aircraft is descending for arrival'];
        }
        if ($alt > 8000) {
            return ['status' => 'Cruising', 'color' => 'text-wimf-400', 'bg' => 'bg-wimf-600/20', 'icon' => '✈️', 'desc' => 'Aircraft at cruising altitude'];
        }
        return ['status' => 'In Flight', 'color' => 'text-sky-400', 'bg' => 'bg-sky-500/20', 'icon' => '✈️', 'desc' => 'Aircraft is in the air'];
    }
}

/**
 * Get booking links for a flight/route.
 */
if (!function_exists('getBookingLinks')) {
    function getBookingLinks(string $origin = '', string $destination = '', string $airlineName = ''): array
    {
        $o = urlencode($origin);
        $d = urlencode($destination);
        $query = urlencode(trim("$origin to $destination $airlineName"));

        return [
            ['name' => 'Google Flights', 'icon' => '🔍', 'color' => 'bg-blue-600', 'url' => "https://www.google.com/travel/flights?q=flights+from+{$o}+to+{$d}"],
            ['name' => 'Skyscanner', 'icon' => '🌐', 'color' => 'bg-cyan-600', 'url' => "https://www.skyscanner.co.in/transport/flights/{$o}/{$d}/"],
            ['name' => 'Kayak', 'icon' => '🛶', 'color' => 'bg-orange-600', 'url' => "https://www.kayak.com/flights/{$o}-{$d}/"],
            ['name' => 'MakeMyTrip', 'icon' => '🇮🇳', 'color' => 'bg-red-600', 'url' => "https://www.makemytrip.com/flight/search?from={$o}&to={$d}"],
            ['name' => 'Goibibo', 'icon' => '🎫', 'color' => 'bg-green-600', 'url' => "https://www.goibibo.com/flights/"],
            ['name' => 'Cleartrip', 'icon' => '✨', 'color' => 'bg-purple-600', 'url' => "https://www.cleartrip.com/flights"],
            ['name' => 'Expedia', 'icon' => '🌍', 'color' => 'bg-yellow-600', 'url' => "https://www.expedia.com/Flights-search?trip=oneway&leg1=from:{$o},to:{$d}"],
        ];
    }
}

/**
 * Get airport coordinates by IATA code.
 */
if (!function_exists('getAirportCoords')) {
    function getAirportCoords(string $iata): ?array
    {
        $db = getAirportDatabase();
        $iata = strtoupper($iata);
        if (isset($db[$iata])) {
            return ['lat' => $db[$iata][0], 'lon' => $db[$iata][1], 'name' => $db[$iata][2], 'city' => $db[$iata][3], 'country' => $db[$iata][4]];
        }
        return null;
    }
}
