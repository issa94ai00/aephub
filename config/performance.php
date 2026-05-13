<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Performance Optimization Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for optimizing the application for high concurrency
    | and scalability.
    |
    */

    'database' => [
        // Enable database connection pooling
        'pooling' => [
            'enabled' => env('DB_POOLING_ENABLED', true),
            'max_connections' => env('DB_MAX_CONNECTIONS', 100),
            'min_connections' => env('DB_MIN_CONNECTIONS', 10),
            'idle_timeout' => env('DB_IDLE_TIMEOUT', 60),
        ],

        // Read replica configuration
        'read_replicas' => [
            'enabled' => env('DB_READ_REPLICAS_ENABLED', false),
            'hosts' => explode(',', env('DB_READ_REPLICA_HOSTS', '')),
            'sticky' => env('DB_READ_REPLICA_STICKY', true),
        ],

        // Query optimization
        'query' => [
            'slow_query_log' => env('DB_SLOW_QUERY_LOG', true),
            'slow_query_threshold' => env('DB_SLOW_QUERY_THRESHOLD', 1000), // milliseconds
            'enable_query_cache' => env('DB_QUERY_CACHE', true),
        ],
    ],

    'cache' => [
        // Redis caching configuration
        'redis' => [
            'enabled' => env('REDIS_ENABLED', true),
            'default_ttl' => env('CACHE_DEFAULT_TTL', 3600),
            'prefix' => env('CACHE_PREFIX', 'aep:'),
            
            // Cache keys for frequently accessed data
            'keys' => [
                'live_session_status' => 'live_session:{id}:status',
                'live_session_participants' => 'live_session:{id}:participants',
                'live_session_events_buffer' => 'live_session:{id}:events_buffer',
                'user_active_sessions' => 'user:{user_id}:active_sessions',
            ],
        ],

        // Cache warming
        'warming' => [
            'enabled' => env('CACHE_WARMING_ENABLED', true),
            'schedule' => env('CACHE_WARMING_SCHEDULE', '*/5 * * * *'),
        ],
    ],

    'queue' => [
        // Queue worker optimization
        'workers' => [
            'default' => [
                'connection' => 'redis',
                'queue' => 'default',
                'sleep' => 3,
                'tries' => 3,
                'timeout' => 60,
                'max_jobs' => 1000,
                'max_time' => 3600,
            ],
            'live_session' => [
                'connection' => 'redis',
                'queue' => 'live-session',
                'sleep' => 1,
                'tries' => 5,
                'timeout' => 120,
                'max_jobs' => 500,
                'max_time' => 1800,
            ],
            'recording' => [
                'connection' => 'redis',
                'queue' => 'recording',
                'sleep' => 5,
                'tries' => 3,
                'timeout' => 300,
                'max_jobs' => 100,
                'max_time' => 7200,
            ],
        ],

        // Queue monitoring
        'monitoring' => [
            'enabled' => env('QUEUE_MONITORING_ENABLED', true),
            'alert_threshold' => env('QUEUE_ALERT_THRESHOLD', 1000),
        ],
    ],

    'rate_limiting' => [
        // API rate limiting
        'api' => [
            'enabled' => env('RATE_LIMITING_ENABLED', true),
            'default' => [
                'max_attempts' => 60,
                'decay_seconds' => 60,
            ],
            'live_session' => [
                'max_attempts' => 100,
                'decay_seconds' => 60,
            ],
            'events' => [
                'max_attempts' => 300,
                'decay_seconds' => 60,
            ],
        ],

        // WebSocket rate limiting
        'websocket' => [
            'enabled' => env('WS_RATE_LIMITING_ENABLED', true),
            'max_connections_per_user' => env('WS_MAX_CONNECTIONS_PER_USER', 5),
            'message_rate_limit' => env('WS_MESSAGE_RATE_LIMIT', 100),
            'message_rate_window' => env('WS_MESSAGE_RATE_WINDOW', 60),
        ],
    ],

    'load_balancing' => [
        'enabled' => env('LOAD_BALANCING_ENABLED', false),
        'algorithm' => env('LOAD_BALANCING_ALGORITHM', 'round_robin'),
        'health_check_interval' => env('HEALTH_CHECK_INTERVAL', 30),
        'unhealthy_threshold' => env('UNHEALTHY_THRESHOLD', 3),
        'healthy_threshold' => env('HEALTHY_THRESHOLD', 2),
    ],

    'auto_scaling' => [
        'enabled' => env('AUTO_SCALING_ENABLED', false),
        'min_instances' => env('AUTO_SCALING_MIN_INSTANCES', 2),
        'max_instances' => env('AUTO_SCALING_MAX_INSTANCES', 10),
        'cpu_threshold_high' => env('AUTO_SCALING_CPU_HIGH', 70),
        'cpu_threshold_low' => env('AUTO_SCALING_CPU_LOW', 30),
        'memory_threshold_high' => env('AUTO_SCALING_MEMORY_HIGH', 80),
        'memory_threshold_low' => env('AUTO_SCALING_MEMORY_LOW', 40),
        'scale_up_cooldown' => env('AUTO_SCALING_SCALE_UP_COOLDOWN', 300),
        'scale_down_cooldown' => env('AUTO_SCALING_SCALE_DOWN_COOLDOWN', 600),
    ],

    'monitoring' => [
        'health_checks' => [
            'enabled' => env('HEALTH_CHECKS_ENABLED', true),
            'endpoint' => '/health',
            'detailed_endpoint' => '/health/detailed',
        ],
        
        'metrics' => [
            'enabled' => env('METRICS_ENABLED', true),
            'endpoint' => '/metrics',
            'collect_interval' => env('METRICS_COLLECT_INTERVAL', 60),
        ],
    ],

    'session' => [
        // Session management optimization
        'driver' => env('SESSION_DRIVER', 'redis'),
        'lifetime' => env('SESSION_LIFETIME', 120),
        'expire_on_close' => false,
        'encrypt' => false,
        'files' => null,
        'connection' => null,
        'table' => 'sessions',
        'store' => null,
        'lottery' => [2, 100],
        'cookie' => env('SESSION_COOKIE', 'aep_session'),
        'path' => '/',
        'domain' => env('SESSION_DOMAIN', null),
        'secure' => env('SESSION_SECURE_COOKIE'),
        'http_only' => true,
        'same_site' => 'lax',
    ],
];
