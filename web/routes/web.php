<?php

/**
 * WHERE IS MY FLIGHT — Web Routes
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FlightController;
use App\Http\Controllers\GateController;
use App\Http\Controllers\AuthController;

// ─── Public Routes ───
Route::get('/', [DashboardController::class, 'index'])->name('home');

// ★ Route flights API (for booking page)
Route::get('/api/route-flights', [FlightController::class, 'apiRouteFlights'])->name('api.route.flights');

// ★ OTA Booking Search (mock aggregator)
Route::get('/api/ota-search', [FlightController::class, 'otaSearch'])->name('api.ota.search');

// ─── Authentication ───
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// ─── Authenticated Routes ───
Route::middleware('auth')->group(function () {

    // ★ Real-time live feed
    Route::get('/live', [DashboardController::class, 'liveFeed'])->name('live.feed');
    Route::get('/api/live-flights', [DashboardController::class, 'apiLiveFlights'])->name('api.live.flights');

    // ★ Flight board
    Route::get('/board/{airport?}', [FlightController::class, 'board'])->name('flights.board');

    // ★ Flight profile (the rich detail page)
    Route::get('/flight/{callsign}', [FlightController::class, 'profile'])->name('flight.profile');

    // ★ Booking aggregator
    Route::get('/book/{callsign?}', [FlightController::class, 'booking'])->name('flights.booking');



    // ★ Search
    Route::get('/search', [FlightController::class, 'search'])->name('flights.search');

    // ★ User dashboard (nearby flights + map)
    Route::get('/dashboard', [FlightController::class, 'userDashboard'])->name('user.dashboard');
    Route::get('/api/nearby-flights', [FlightController::class, 'apiNearbyFlights'])->name('api.nearby.flights');

    // ★ Aircraft info API (for AJAX)
    Route::get('/api/aircraft/{icao24}', [FlightController::class, 'apiAircraftInfo'])->name('api.aircraft.info');



    // Flight tracking
    Route::post('/flight-watch/{flight}/watch', [FlightController::class, 'watch'])->name('flights.watch');
    Route::delete('/flight-watch/{flight}/watch', [FlightController::class, 'unwatch'])->name('flights.unwatch');
    Route::get('/my-flights', [FlightController::class, 'myFlights'])->name('flights.mine');

    // Gate contributions
    Route::get('/gate/edit/{flight}', [GateController::class, 'edit'])->name('gates.edit');
    Route::post('/gate/submit', [GateController::class, 'submit'])->name('gates.submit');
    Route::post('/gate/{contribution}/corroborate', [GateController::class, 'corroborate'])->name('gates.corroborate');

    // User profile
    Route::get('/profile', [DashboardController::class, 'profile'])->name('profile');
    Route::get('/my-contributions', [GateController::class, 'myContributions'])->name('contributions.mine');
});

// ─── Moderator Routes ───
Route::middleware(['auth', 'moderator'])->prefix('mod')->group(function () {
    Route::get('/queue', [GateController::class, 'moderationQueue'])->name('mod.queue');
    Route::post('/approve/{contribution}', [GateController::class, 'approve'])->name('mod.approve');
    Route::post('/reject/{contribution}', [GateController::class, 'reject'])->name('mod.reject');
});
