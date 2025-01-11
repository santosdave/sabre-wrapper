<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    */
    'enabled' => env('SABRE_RATE_LIMITING_ENABLED', true),
    'default_window' => env('SABRE_RATE_LIMITING_WINDOW', 60),
    'default_limit' => env('SABRE_RATE_LIMITING_LIMIT', 100),
    'decay_minutes' => env('SABRE_RATE_LIMITING_DECAY', 1),

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'prefix' => 'sabre_rate_limit:',
        'store' => env('SABRE_RATE_LIMITING_STORE', 'redis'),
        'ttl' => env('SABRE_RATE_LIMITING_CACHE_TTL', 3600)
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring & Alerts
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'enabled' => env('SABRE_RATE_LIMITING_MONITORING', true),
        'alert_threshold' => env('SABRE_RATE_LIMITING_ALERT_THRESHOLD', 80), // percentage
        'notification_channels' => ['slack', 'email'],
        'alert_cooldown' => 300 // 5 minutes between alerts
    ],

    /*
    |--------------------------------------------------------------------------
    | API Endpoints Rate Limits
    |--------------------------------------------------------------------------
    */
    'limits' => [
        'shopping' => [
            'bargain_finder_max' => [
                'limit' => env('SABRE_RATE_LIMIT_BFM', 50),
                'window' => 60,
                'priority' => 'high'
            ],
            'alternative_dates' => [
                'limit' => env('SABRE_RATE_LIMIT_ALT_DATES', 30),
                'window' => 60,
                'priority' => 'medium'
            ],
            'insta_flights' => [
                'limit' => env('SABRE_RATE_LIMIT_INSTA', 40),
                'window' => 60,
                'priority' => 'medium'
            ]
        ],
        'booking' => [
            'create' => [
                'limit' => env('SABRE_RATE_LIMIT_BOOKING_CREATE', 20),
                'window' => 60,
                'priority' => 'critical'
            ],
            'modify' => [
                'limit' => env('SABRE_RATE_LIMIT_BOOKING_MODIFY', 30),
                'window' => 60,
                'priority' => 'high'
            ],
            'cancel' => [
                'limit' => env('SABRE_RATE_LIMIT_BOOKING_CANCEL', 25),
                'window' => 60,
                'priority' => 'high'
            ]
        ],
        'orders' => [
            'create' => [
                'limit' => env('SABRE_RATE_LIMIT_ORDER_CREATE', 20),
                'window' => 60,
                'priority' => 'critical'
            ],
            'fulfill' => [
                'limit' => env('SABRE_RATE_LIMIT_ORDER_FULFILL', 15),
                'window' => 60,
                'priority' => 'critical'
            ],
            'view' => [
                'limit' => env('SABRE_RATE_LIMIT_ORDER_VIEW', 100),
                'window' => 60,
                'priority' => 'low'
            ]
        ],
        'seats' => [
            'get_map' => [
                'limit' => env('SABRE_RATE_LIMIT_SEAT_MAP', 50),
                'window' => 60,
                'priority' => 'medium'
            ],
            'assign' => [
                'limit' => env('SABRE_RATE_LIMIT_SEAT_ASSIGN', 30),
                'window' => 60,
                'priority' => 'high'
            ]
        ],
        'authentication' => [
            'token_create' => [
                'limit' => env('SABRE_RATE_LIMIT_AUTH_TOKEN', 10),
                'window' => 60,
                'priority' => 'critical'
            ],
            'session_create' => [
                'limit' => env('SABRE_RATE_LIMIT_AUTH_SESSION', 5),
                'window' => 60,
                'priority' => 'critical'
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Strategy
    |--------------------------------------------------------------------------
    */
    'retry' => [
        'enabled' => true,
        'max_attempts' => 3,
        'initial_delay' => 1000, // milliseconds
        'multiplier' => 2,
        'max_delay' => 10000 // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Headers
    |--------------------------------------------------------------------------
    */
    'headers' => [
        'limit' => 'X-RateLimit-Limit',
        'remaining' => 'X-RateLimit-Remaining',
        'reset' => 'X-RateLimit-Reset',
        'retry_after' => 'Retry-After'
    ]
];