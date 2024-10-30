<?php

namespace App\Services\StoreIsolation;

use App\Models\StoreSilo;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class StoreDestroyer
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

    public function destroy(StoreSilo $silo): void
    {
        try {
            // First, connect to store database to get any cleanup info needed
            $this->connection->connect($silo);
            
            // Clean up store files
            $this->cleanupStoreFiles($silo);
            
            // Disconnect from store database
            $this->connection->disconnect();
            
            // Delete the store's SQLite database
            $this->databaseManager->deleteStoreDatabase($silo);
            
            // Remove store directories
            $this->removeStoreDirectories($silo);
            
            // Finally, delete the silo record
            $silo->delete();

        } catch (\Exception $e) {
            Log::error('Failed to destroy store', [
                'silo_id' => $silo->id,
                'error' => $e->getMessage(),
            ]);
            
            throw new \RuntimeException(
                'Failed to completely destroy store. Manual cleanup may be required.',
                0,
                $e
            );
        }
    }

    private function cleanupStoreFiles(StoreSilo $silo): void
    {
        $basePath = storage_path("store-files/{$silo->id}");
        
        if (File::exists($basePath)) {
            // Recursively remove all store files
            File::deleteDirectory($basePath);
        }
    }

    private function removeStoreDirectories(StoreSilo $silo): void
    {
        $directories = [
            storage_path("store-files/{$silo->id}"),
        ];

        foreach ($directories as $dir) {
            if (File::exists($dir)) {
                File::deleteDirectory($dir);
            }
        }
    }
}
