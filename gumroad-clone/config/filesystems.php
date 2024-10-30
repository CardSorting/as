<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        'store_files' => [
            'driver' => 'local',
            'root' => storage_path('store-files'),
            'throw' => false,
            'permissions' => [
                'file' => [
                    'public' => 0600,
                    'private' => 0600,
                ],
                'dir' => [
                    'public' => 0755,
                    'private' => 0755,
                ],
            ],
        ],
    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
