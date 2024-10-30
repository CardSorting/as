<?php

return [
    'tiers' => [
        'basic' => [
            'queue' => 'store-basic',
            'workers' => 1,
            'tries' => 3,
            'timeout' => 60,
            'concurrent_jobs' => 5,
        ],
        'pro' => [
            'queue' => 'store-pro',
            'workers' => 2,
            'tries' => 5,
            'timeout' => 120,
            'concurrent_jobs' => 10,
        ],
        'enterprise' => [
            'queue' => 'store-enterprise',
            'workers' => 4,
            'tries' => 7,
            'timeout' => 180,
            'concurrent_jobs' => 20,
        ],
    ],
    
    'store_operations' => [
        'creation' => 'store-creation',
        'deployment' => 'store-deployment',
        'database' => 'store-database',
    ],
];
