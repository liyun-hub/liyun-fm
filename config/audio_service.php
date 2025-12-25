<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Python Audio Service Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file contains the settings for the Python audio service
    | that handles FFmpeg stream processing.
    |
    */

    // Python service URL
    'url' => env('AUDIO_SERVICE_URL', 'http://localhost:5000'),
    
    // Python service timeout (in seconds, 0 for unlimited)
    'timeout' => env('AUDIO_SERVICE_TIMEOUT', 30),
    
    // Service health check cache duration (in seconds)
    'health_check_cache_duration' => env('AUDIO_SERVICE_HEALTH_CACHE', 30),
    
    // Request retry configuration
    'retry_attempts' => env('AUDIO_SERVICE_RETRY_ATTEMPTS', 3),
    'retry_delay' => env('AUDIO_SERVICE_RETRY_DELAY', 1),
    
    // HLS configuration
    'hls' => [
        'segment_cache_duration' => env('AUDIO_SERVICE_HLS_CACHE', 30), // seconds
        'playlist_timeout' => env('AUDIO_SERVICE_HLS_TIMEOUT', 30), // seconds
    ],
];
