<?php

namespace App\Services\StoreIsolation;

use App\Models\StoreSilo;
use App\Models\Store\Settings;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class StoreCreator
{
    private DatabaseManager $databaseManager;
    private StoreConnection $connection;

    public function __construct(
        DatabaseManager $databaseManager,
        StoreConnection $connection
    ) {
        $this->databaseManager = $databaseManager;
        $this->connection = $connection;
    }

    public function create(array $data): StoreSilo
    {
        // Create the store silo record
        $silo = StoreSilo::create([
            'user_id' => $data['user_id'],
            'store_domain' => $data['domain'],
        ]);

        // Create store directory structure
        $this->createStoreDirectories($silo);

        // Create and initialize store database
        $this->databaseManager->createStoreDatabase($silo);

        // Connect to the new store database
        $this->connection->connect($silo);

        try {
            // Initialize store settings
            Settings::create([
                'theme_config' => [
                    'colors' => [
                        'primary' => '#4F46E5',
                        'secondary' => '#6B7280',
                    ],
                    'fonts' => [
                        'heading' => 'Inter',
                        'body' => 'Inter',
                    ],
                ],
                'payment_settings' => [
                    'currency' => 'USD',
                    'methods' => ['card'],
                ],
                'notification_settings' => [
                    'email' => [
                        'sales' => true,
                        'refunds' => true,
                    ],
                ],
            ]);
        } finally {
            // Always disconnect from store database
            $this->connection->disconnect();
        }

        return $silo;
    }

    private function createStoreDirectories(StoreSilo $silo): void
    {
        // Create store-specific directories
        $paths = [
            storage_path("store-files/{$silo->id}"),
            storage_path("store-files/{$silo->id}/products"),
            storage_path("store-files/{$silo->id}/downloads"),
            storage_path("store-files/{$silo->id}/temp"),
        ];

        foreach ($paths as $path) {
            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
            }
        }

        // Create .gitignore to prevent accidental commits of store files
        File::put(storage_path("store-files/{$silo->id}/.gitignore"), "*\n!.gitignore\n");
    }
}
