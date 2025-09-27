<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Production Security Configuration
    |--------------------------------------------------------------------------
    */
    
    'security' => [
        'force_https' => env('FORCE_HTTPS', true),
        'csp_enabled' => env('CSP_ENABLED', true),
        'hsts_max_age' => env('HSTS_MAX_AGE', 31536000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    */
    
    'performance' => [
        'enable_opcache' => env('OPCACHE_ENABLE', true),
        'cache_ttl' => env('CACHE_TTL', 3600),
        'query_log_enabled' => env('ENABLE_QUERY_LOG', false),
        'slow_query_time' => env('DB_LONG_QUERY_TIME', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring Configuration
    |--------------------------------------------------------------------------
    */
    
    'monitoring' => [
        'enabled' => env('PERFORMANCE_MONITORING', false),
        'memory_threshold' => env('MEMORY_THRESHOLD', 85),
        'cpu_threshold' => env('CPU_THRESHOLD', 80),
        'disk_threshold' => env('DISK_THRESHOLD', 85),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    
    'rate_limiting' => [
        'api_rate' => env('THROTTLE_API', '60,1'),
        'login_rate' => env('THROTTLE_LOGIN', '5,1'),
        'general_rate' => env('THROTTLE_GENERAL', '1000,1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Configuration
    |--------------------------------------------------------------------------
    */
    
    'backup' => [
        'enabled' => env('BACKUP_ENABLED', false),
        'disk' => env('BACKUP_DISK', 'local'),
        'schedule' => env('BACKUP_SCHEDULE', 'daily'),
        'retention_days' => env('BACKUP_RETENTION_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Asset Configuration
    |--------------------------------------------------------------------------
    */
    
    'assets' => [
        'version' => env('ASSET_VERSION', '1.0.0'),
        'cdn_enabled' => env('CDN_ENABLED', false),
        'cdn_url' => env('CDN_URL', null),
        'cache_busting' => env('ASSET_CACHE_BUSTING', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Check Configuration
    |--------------------------------------------------------------------------
    */
    
    'health' => [
        'enabled' => env('HEALTH_CHECK_ENABLED', true),
        'endpoints' => [
            'database' => env('HEALTH_CHECK_DATABASE', true),
            'cache' => env('HEALTH_CHECK_CACHE', true),
            'storage' => env('HEALTH_CHECK_STORAGE', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Production Optimizations
    |--------------------------------------------------------------------------
    */
    
    'optimizations' => [
        'config_cache' => env('CONFIG_CACHE_ENABLED', true),
        'route_cache' => env('ROUTE_CACHE_ENABLED', true),
        'view_cache' => env('VIEW_CACHE_ENABLED', true),
        'event_cache' => env('EVENT_CACHE_ENABLED', true),
        'query_cache' => env('QUERY_CACHE_ENABLED', true),
    ],
];