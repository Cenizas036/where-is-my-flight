<?php

namespace App\Services;

use App\Models\GateContribution;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * PlayApiClient — HTTP client for communicating with the Scala/Play backend.
 * 
 * Laravel acts as the API gateway; this service proxies requests to
 * the Play Framework server which handles WebSocket broadcasting,
 * Kafka consumption, and Spark prediction results.
 */
class PlayApiClient
{
    private string $baseUrl;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('wimf.play_api_host', 'http://play-api:9000');
        $this->timeout = config('wimf.play_api_timeout', 30);
    }

    /**
     * Get the flight board for an airport.
     * Cached for 30 seconds to reduce load on Play backend.
     */
    public function getFlightBoard(string $airportIata, string $type = 'departures'): array
    {
        $cacheKey = "board:{$airportIata}:{$type}";

        return Cache::remember($cacheKey, 30, function () use ($airportIata, $type) {
            try {
                $response = Http::timeout($this->timeout)
                    ->get("{$this->baseUrl}/api/v1/flights/board/{$airportIata}", [
                        'type' => $type,
                    ]);

                if ($response->successful()) {
                    return $response->json('flights', []);
                }

                Log::warning('Play API board request failed', [
                    'airport' => $airportIata,
                    'status'  => $response->status(),
                ]);

                return [];
            } catch (\Exception $e) {
                Log::error('Play API connection error', [
                    'error' => $e->getMessage(),
                ]);
                return [];
            }
        });
    }

    /**
     * Get airport information from the Play backend.
     */
    public function getAirportInfo(string $airportIata): ?array
    {
        return Cache::remember("airport:{$airportIata}", 3600, function () use ($airportIata) {
            try {
                $response = Http::timeout($this->timeout)
                    ->get("{$this->baseUrl}/api/v1/airports/{$airportIata}");

                return $response->successful() ? $response->json() : null;
            } catch (\Exception $e) {
                Log::error('Play API airport info error', ['error' => $e->getMessage()]);
                return null;
            }
        });
    }

    /**
     * Get delay prediction for a specific flight.
     * This comes from the Spark ML pipeline via Play.
     */
    public function getFlightPrediction(string $flightId): ?array
    {
        $cacheKey = "prediction:{$flightId}";

        return Cache::remember($cacheKey, 60, function () use ($flightId) {
            try {
                $response = Http::timeout($this->timeout)
                    ->get("{$this->baseUrl}/api/v1/predictions/{$flightId}");

                if ($response->successful()) {
                    return $response->json();
                }

                return null;
            } catch (\Exception $e) {
                Log::error('Play API prediction error', ['error' => $e->getMessage()]);
                return null;
            }
        });
    }

    /**
     * Broadcast a gate update to all connected WebSocket clients.
     * Laravel → Play API → Redis pub/sub → Browser WebSockets
     */
    public function broadcastGateUpdate(GateContribution $contribution): bool
    {
        try {
            $response = Http::timeout(10)
                ->post("{$this->baseUrl}/api/v1/broadcast/gate-update", [
                    'flight_id'   => $contribution->flight_id,
                    'gate_number' => $contribution->gate_number,
                    'terminal'    => $contribution->terminal,
                    'confidence'  => $contribution->confidence_score,
                    'contributor' => $contribution->user->display_name,
                    'source'      => 'community',
                    'timestamp'   => now()->toIso8601String(),
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Gate broadcast failed', [
                'contribution_id' => $contribution->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send a flight status update to be broadcast.
     */
    public function broadcastFlightUpdate(array $flightData): bool
    {
        try {
            $response = Http::timeout(10)
                ->post("{$this->baseUrl}/api/v1/broadcast/flight-update", $flightData);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Flight broadcast failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
