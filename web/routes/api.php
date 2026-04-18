<?php

/**
 * WHERE IS MY FLIGHT — API Routes
 * 
 * These routes are prefixed with /api/ and handle
 * AJAX requests from the Scala.js frontend components.
 * Heavy lifting is proxied to the Play Framework backend.
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FlightController;
use App\Http\Controllers\GateController;

// ─────────────────────────────────────────────
// Public API
// ─────────────────────────────────────────────

Route::prefix('flights')->group(function () {
    // Get departure/arrival board for an airport
    Route::get('/board/{airportIata}', [FlightController::class, 'apiBoard']);
    
    // Search flights by number or route
    Route::get('/search', [FlightController::class, 'apiSearch']);
    
    // Get single flight status (polled by Scala.js when WS unavailable)
    Route::get('/{flightId}/status', [FlightController::class, 'apiStatus']);
    
    // Get delay prediction for a flight
    Route::get('/{flightId}/prediction', [FlightController::class, 'apiPrediction']);
});

// ─────────────────────────────────────────────
// Authenticated API
// ─────────────────────────────────────────────

Route::middleware('auth:sanctum')->group(function () {
    
    // Gate contributions via API
    Route::post('/gates/submit', [GateController::class, 'apiSubmit']);
    Route::post('/gates/{contributionId}/corroborate', [GateController::class, 'apiCorroborate']);
    
    // Get current gate info (community + official)
    Route::get('/gates/flight/{flightId}', [GateController::class, 'apiGateInfo']);
    
    // User's watched flights
    Route::get('/user/watches', [FlightController::class, 'apiWatches']);
    Route::post('/user/watches/{flightId}', [FlightController::class, 'apiAddWatch']);
    Route::delete('/user/watches/{flightId}', [FlightController::class, 'apiRemoveWatch']);
});
