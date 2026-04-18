<?php

/**
 * WHERE IS MY FLIGHT — Custom Configuration
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Play Framework API
    |--------------------------------------------------------------------------
    */
    'play_api_host'    => env('PLAY_API_HOST', 'http://play-api:9000'),
    'play_api_timeout' => env('PLAY_API_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | WebSocket Configuration
    |--------------------------------------------------------------------------
    */
    'websocket_url'  => env('WEBSOCKET_URL', 'ws://localhost:9000'),
    'websocket_port' => env('WEBSOCKET_PORT', 6001),

    /*
    |--------------------------------------------------------------------------
    | Flight Data API
    |--------------------------------------------------------------------------
    */
    'flight_api_provider' => env('FLIGHT_API_PROVIDER', 'aviationstack'),
    'aviationstack_key'   => env('AVIATIONSTACK_API_KEY'),
    'flightaware_key'     => env('FLIGHTAWARE_API_KEY'),
    'flightaware_secret'  => env('FLIGHTAWARE_API_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Trust Scoring Thresholds
    |--------------------------------------------------------------------------
    */
    'trust' => [
        'auto_approve_threshold' => env('TRUST_AUTO_APPROVE_THRESHOLD', 0.85),
        'live_threshold'         => env('TRUST_SCORE_THRESHOLD', 0.65),
        'corroboration_window'   => env('TRUST_CORROBORATION_WINDOW_MINUTES', 15),
        'min_corroborations'     => env('TRUST_MIN_CORROBORATIONS', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Board Defaults
    |--------------------------------------------------------------------------
    */
    'default_airport'     => env('DEFAULT_AIRPORT', 'JFK'),
    'board_refresh_rate'  => env('BOARD_REFRESH_RATE', 30), // seconds
    'max_board_flights'   => env('MAX_BOARD_FLIGHTS', 100),
];
