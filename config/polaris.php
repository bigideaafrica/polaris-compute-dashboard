<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Polaris API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for connecting to the Polaris compute resources API
    |
    */

    'api' => [
        'base_url' => env('POLARIS_API_BASE_URL', 'https://polaris-interface.onrender.com'),
        'headers' => [
            'X-API-Key' => env('POLARIS_API_KEY', 'dev-services-key'),
            'service-key' => env('POLARIS_SERVICE_KEY', '9e2e9d9d4370ba4c6ab90b7ab46ed334bb6b1a79af368b451796a6987988ed77'),
            'service-name' => env('POLARIS_SERVICE_NAME', 'miner_service'),
            'x-use-encryption' => 'true',
            'Accept' => '*/*',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Connection' => 'keep-alive',
            'User-Agent' => 'PolarisCompute/1.0.0'
        ],
        'endpoints' => [
            'compute_resources' => '/api/v1/services/miner/compute-resources',
            'miners' => '/api/v1/miners',
            'pods' => '/api/v1/pods',
            'miner_states' => '/api/v1/miner-states',
            'subscriptions' => '/api/v1/subscriptions',
        ],
        'alternative_endpoints' => [
            '/api/v1/services/miner/miners',
            '/api/v1/services/compute/resources',
            '/api/v1/compute-resources',
            '/api/v1/services/miner/resources'
        ],
        'timeout' => 30,
        'connect_timeout' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Filter Configuration
    |--------------------------------------------------------------------------
    |
    | Enable/disable various filtering options
    |
    */

    'filters' => [
        'fake_gpu_filter' => env('ENABLE_FAKE_GPU_FILTER', true),
        'storage_filter' => env('ENABLE_STORAGE_FILTER', true),
        'verification_filter' => env('ENABLE_VERIFICATION_FILTER', true),
        'monitoring_filter' => env('ENABLE_MONITORING_FILTER', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Refresh Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for automatic data refreshing
    |
    */

    'refresh' => [
        'auto_refresh_interval' => 15, // seconds
        'cache_ttl' => 30, // seconds
        'max_retries' => 3,
        'retry_delay' => 5, // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Display Settings
    |--------------------------------------------------------------------------
    |
    | UI display configuration
    |
    */

    'display' => [
        'resources_per_page' => 50,
        'skeleton_cards' => 20,
        'default_sort' => 'gpu_first', // gpu_first, cpu_first, memory_desc, cores_desc
        'show_pricing' => true,
        'show_location' => true,
        'show_monitoring_status' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring Health Checks
    |--------------------------------------------------------------------------
    |
    | Required status checks for resources to be considered healthy
    |
    */

    'health_checks' => [
        'auth_status' => 'ok',
        'connection_status' => 'ok',
        'docker_running' => true,
        'docker_user_group' => true,
    ],
];