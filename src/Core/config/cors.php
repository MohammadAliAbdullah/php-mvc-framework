<?php

declare(strict_types=1);

/**
 * CORS Configuration
 * 
 * This file contains configuration options for the CorsMiddleware.
 * You can customize these settings based on your environment and requirements.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | CORS Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for Cross-Origin Resource Sharing (CORS).
    | You can enable/disable CORS and configure allowed origins, methods, and headers.
    |
    */

    // Enable or disable CORS globally
    'enabled' => $_ENV['CORS_ENABLED'] ?? $_SERVER['CORS_ENABLED'] ?? true,

    // Allowed origins (comma-separated string or array)
    'allowed_origins' => $_ENV['CORS_ALLOWED_ORIGINS'] ?? $_SERVER['CORS_ALLOWED_ORIGINS'] ?? [
        'http://localhost:3000',
        'http://localhost:5173',
        'http://localhost:8080',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:5173',
        'http://127.0.0.1:8080',
    ],

    // Allowed HTTP methods
    'allowed_methods' => [
        'GET',
        'POST',
        'PUT',
        'DELETE',
        'OPTIONS',
        'PATCH'
    ],

    // Allowed headers
    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'Accept',
        'Cache-Control',
        'X-CSRF-TOKEN',
        'X-API-Key'
    ],

    // Exposed headers (headers that browsers are allowed to access)
    'exposed_headers' => [],

    // Allow credentials (cookies, authorization headers, etc.)
    'allow_credentials' => $_ENV['CORS_ALLOW_CREDENTIALS'] ?? $_SERVER['CORS_ALLOW_CREDENTIALS'] ?? true,

    // Max age for preflight requests (in seconds)
    'max_age' => $_ENV['CORS_MAX_AGE'] ?? $_SERVER['CORS_MAX_AGE'] ?? 86400, // 24 hours

    // Development environment
    'development' => [
        'allowed_origins' => [
            'http://localhost:3000',
            'http://localhost:5173',
            'http://localhost:8080',
            'http://127.0.0.1:3000',
            'http://127.0.0.1:5173',
            'http://127.0.0.1:8080',
        ],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'PATCH'],
        'allowed_headers' => [
            'Content-Type',
            'Authorization',
            'X-Requested-With',
            'Accept',
            'Cache-Control',
            'X-CSRF-TOKEN',
            'X-API-Key'
        ],
        'exposed_headers' => [],
        'allow_credentials' => true,
        'max_age' => 86400, // 24 hours
    ],

    // Production environment
    'production' => [
        'allowed_origins' => [
            'https://yourdomain.com',
            'https://www.yourdomain.com',
            'https://api.yourdomain.com',
        ],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'allowed_headers' => [
            'Content-Type',
            'Authorization',
            'X-Requested-With',
            'Accept',
            'X-CSRF-TOKEN',
            'X-API-Key'
        ],
        'exposed_headers' => [],
        'allow_credentials' => true,
        'max_age' => 86400,
    ],

    // Testing environment
    'testing' => [
        'allowed_origins' => ['*'], // Allow all origins for testing
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'PATCH'],
        'allowed_headers' => ['*'], // Allow all headers for testing
        'exposed_headers' => [],
        'allow_credentials' => false, // Disable credentials for testing
        'max_age' => 86400,
    ],
]; 