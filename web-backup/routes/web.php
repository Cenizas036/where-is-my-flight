<?php

/**
 * WHERE IS MY FLIGHT — Web Routes
 * 
 * These routes serve the Blade-rendered pages.
 * API calls to the Play backend are proxied through /api/v1/
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FlightController;
use App\Http\Controllers\GateController;
use App\Http\Controllers\AuthController;

// ─────────────────────────────────────────────
// Public Routes
// ─────────────────────────────────────────────

Route::get('/', [DashboardController::class, 'index'])->name('home');

// Flight board — the main live departure/arrival board
Route::get('/board/{airport?}', [FlightController::class, 'board'])->name('flights.board');

// Individual flight detail page
Route::get('/flight/{flightNumber}/{date?}', [FlightController::class, 'show'])->name('flights.show');

// Airport search
Route::get('/search', [FlightController::class, 'search'])->name('flights.search');

// ─────────────────────────────────────────────
// Authentication
// ─────────────────────────────────────────────

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// ─────────────────────────────────────────────
// Authenticated Routes
// ─────────────────────────────────────────────

Route::middleware('auth')->group(function () {

    // Flight tracking (watch/unwatch)
    Route::post('/flight/{flight}/watch', [FlightController::class, 'watch'])->name('flights.watch');
    Route::delete('/flight/{flight}/watch', [FlightController::class, 'unwatch'])->name('flights.unwatch');
    Route::get('/my-flights', [FlightController::class, 'myFlights'])->name('flights.mine');

    // Gate contributions
    Route::get('/gate/edit/{flight}', [GateController::class, 'edit'])->name('gates.edit');
    Route::post('/gate/submit', [GateController::class, 'submit'])->name('gates.submit');
    Route::post('/gate/{contribution}/corroborate', [GateController::class, 'corroborate'])->name('gates.corroborate');

    // User profile & contribution history
    Route::get('/profile', [DashboardController::class, 'profile'])->name('profile');
    Route::get('/my-contributions', [GateController::class, 'myContributions'])->name('contributions.mine');
});

// ─────────────────────────────────────────────
// Moderator Routes
// ─────────────────────────────────────────────

Route::middleware(['auth', 'moderator'])->prefix('mod')->group(function () {
    Route::get('/queue', [GateController::class, 'moderationQueue'])->name('mod.queue');
    Route::post('/approve/{contribution}', [GateController::class, 'approve'])->name('mod.approve');
    Route::post('/reject/{contribution}', [GateController::class, 'reject'])->name('mod.reject');
});
