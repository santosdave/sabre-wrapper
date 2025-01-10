<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sabre API Environment
    |--------------------------------------------------------------------------
    |
    | This value determines which Sabre API environment to use.
    | Supported: "cert", "prod"
    |
    */
    'environment' => env('SABRE_ENVIRONMENT', 'cert'),

    /*
    |--------------------------------------------------------------------------
    | API Endpoints
    |--------------------------------------------------------------------------
    |
    | These are the base URLs for both REST and SOAP APIs in different environments
    |
    */
    'endpoints' => [
        'cert' => [
            'rest' => 'https://api.cert.platform.sabre.com',
            'soap' => 'https://webservices.cert.platform.sabre.com'
        ],
        'prod' => [
            'rest' => 'https://api.platform.sabre.com',
            'soap' => 'https://webservices.platform.sabre.com'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | API Credentials
    |--------------------------------------------------------------------------
    |
    | Your Sabre API credentials
    |
    */
    'credentials' => [
        'username' => env('SABRE_USERNAME'),
        'password' => env('SABRE_PASSWORD'),
        'pcc' => env('SABRE_PCC'),
        'client_id' => env('SABRE_CLIENT_ID'),
        'client_secret' => env('SABRE_CLIENT_SECRET')
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Settings
    |--------------------------------------------------------------------------
    |
    | Configure authentication behavior
    |
    */
    'auth' => [
        'default_method' => env('SABRE_AUTH_METHOD', 'rest'),

        // Token lifetimes in seconds
        'token_lifetime' => [
            'rest' => env('SABRE_REST_TOKEN_LIFETIME', 604800), // 7 days
            'soap_session' => env('SABRE_SOAP_SESSION_LIFETIME', 900), // 15 minutes
            'soap_stateless' => env('SABRE_SOAP_STATELESS_LIFETIME', 604800), // 7 days
        ],

        // Refresh thresholds in seconds
        'refresh_thresholds' => [
            'rest' => env('SABRE_REST_REFRESH_THRESHOLD', 300), // 5 minutes before expiry
            'soap_session' => env('SABRE_SOAP_SESSION_REFRESH_THRESHOLD', 60), // 1 minute before expiry
            'soap_stateless' => env('SABRE_SOAP_STATELESS_REFRESH_THRESHOLD', 3600), // 1 hour before expiry
        ],
        // Session pool configuration
        'session_pool' => [
            'enabled' => env('SABRE_SESSION_POOL_ENABLED', true),
            'size' => env('SABRE_SESSION_POOL_SIZE', 5),
            'cleanup_interval' => env('SABRE_SESSION_POOL_CLEANUP_INTERVAL', 900), // 15 minutes
            'lock_timeout' => env('SABRE_SESSION_POOL_LOCK_TIMEOUT', 10), // 10 seconds
        ],
        // Retry configuration
        'retry' => [
            'max_attempts' => env('SABRE_AUTH_RETRY_MAX_ATTEMPTS', 3),
            'delay_ms' => env('SABRE_AUTH_RETRY_DELAY_MS', 1000),
            'multiplier' => env('SABRE_AUTH_RETRY_MULTIPLIER', 2),
        ],
        'version' => [
            'rest' => 'v3',
            'soap_session' => '2.0.0',
            'soap_stateless' => '2.0.0'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Settings
    |--------------------------------------------------------------------------
    |
    | Configure request behavior
    |
    */
    'request' => [
        'timeout' => env('SABRE_REQUEST_TIMEOUT', 30),
        'retries' => env('SABRE_REQUEST_RETRIES', 3),
        'retry_delay' => env('SABRE_REQUEST_RETRY_DELAY', 1000),
        'verify_ssl' => env('SABRE_VERIFY_SSL', true)
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching Settings
    |--------------------------------------------------------------------------
    |
    | Configure caching behavior
    |
    */
    'cache' => [
        'enabled' => env('SABRE_CACHE_ENABLED', true),
        'ttl' => env('SABRE_CACHE_TTL', 3600),
        'prefix' => env('SABRE_CACHE_PREFIX', 'sabre:')
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Settings
    |--------------------------------------------------------------------------
    |
    | Configure logging behavior
    |
    */
    'logging' => [
        'enabled' => env('SABRE_LOGGING_ENABLED', true),
        'channel' => env('SABRE_LOG_CHANNEL', 'sabre'),
        'level' => env('SABRE_LOG_LEVEL', 'debug'),
        'path' => storage_path('logs/sabre.log')
    ],


    /*
    |--------------------------------------------------------------------------
    | Queuing Settings
    |--------------------------------------------------------------------------
    |
    | Configure queuing behavior
    |
    */

    'queues' => [
        'default_queue' => env('SABRE_DEFAULT_QUEUE', '100'),
        'default_category' => env('SABRE_DEFAULT_QUEUE_CATEGORY', '0'),
        'auto_remove' => env('SABRE_QUEUE_AUTO_REMOVE', true),
        'polling' => [
            'enabled' => env('SABRE_QUEUE_POLLING_ENABLED', false),
            'interval' => env('SABRE_QUEUE_POLLING_INTERVAL', 300), // 5 minutes
            'max_items' => env('SABRE_QUEUE_POLLING_MAX_ITEMS', 50)
        ],
        'retry' => [
            'attempts' => env('SABRE_QUEUE_RETRY_ATTEMPTS', 3),
            'delay' => env('SABRE_QUEUE_RETRY_DELAY', 5)
        ]
    ],
];