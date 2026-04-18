<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * AircraftDataService — Fetches aircraft details and photos from HexDB.io.
 * 
 * - GET https://hexdb.io/api/v1/aircraft/{icao24} → Aircraft info (registration, type, manufacturer)
 * - GET https://hexdb.io/hex-image?hex={icao24} → Aircraft photo URL
 */
class AircraftDataService
{
    /**
     * Get aircraft details by ICAO24 hex code.
     * Cached for 1 hour.
     */
    public function getAircraftInfo(string $icao24): ?array
    {
        $icao24 = strtoupper(trim($icao24));
        
        return Cache::remember("aircraft:info:{$icao24}", 3600, function () use ($icao24) {
            try {
                $response = Http::withoutVerifying()
                    ->timeout(8)
                    ->get("https://hexdb.io/api/v1/aircraft/{$icao24}");

                if ($response->successful()) {
                    $data = $response->json();
                    if ($data && isset($data['ModeS'])) {
                        return [
                            'icao24'       => $data['ModeS'] ?? $icao24,
                            'registration' => $data['Registration'] ?? 'Unknown',
                            'manufacturer' => $data['Manufacturer'] ?? 'Unknown',
                            'type'         => $data['Type'] ?? 'Unknown',
                            'type_code'    => $data['ICAOTypeCode'] ?? '',
                            'owner'        => $data['RegisteredOwners'] ?? 'Unknown',
                            'flag_code'    => $data['OperatorFlagCode'] ?? '',
                        ];
                    }
                }
                return null;
            } catch (\Exception $e) {
                Log::debug('HexDB aircraft info error', ['icao24' => $icao24, 'error' => $e->getMessage()]);
                return null;
            }
        });
    }

    /**
     * Get aircraft photo URL by ICAO24 hex code.
     * Cached for 1 hour.
     */
    public function getAircraftPhoto(string $icao24): ?string
    {
        $icao24 = strtoupper(trim($icao24));

        return Cache::remember("aircraft:photo:{$icao24}", 3600, function () use ($icao24) {
            try {
                $response = Http::withoutVerifying()
                    ->timeout(8)
                    ->get("https://hexdb.io/hex-image", ['hex' => $icao24]);

                if ($response->successful()) {
                    $url = trim($response->body());
                    // Validate it looks like a URL
                    if (str_starts_with($url, 'http')) {
                        return $url;
                    }
                }
                return null;
            } catch (\Exception $e) {
                Log::debug('HexDB photo error', ['icao24' => $icao24, 'error' => $e->getMessage()]);
                return null;
            }
        });
    }

    /**
     * Get full aircraft profile (info + photo) by ICAO24.
     */
    public function getFullProfile(string $icao24): array
    {
        return [
            'info'  => $this->getAircraftInfo($icao24),
            'photo' => $this->getAircraftPhoto($icao24),
        ];
    }
}
