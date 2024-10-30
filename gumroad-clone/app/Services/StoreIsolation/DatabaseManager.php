<?php

namespace App\Services\StoreIsolation;

use App\Models\StoreSilo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class DatabaseManager
{
    public function createStoreDatabase(StoreSilo $store): void
    {
        $path = $this->getDatabasePath($store);
        
        // Create database directory if it doesn't exist
        File::ensureDirectoryExists(dirname($path));
        
        // Create SQLite database file
        if (!File::exists($path)) {
            File::put($path, '');
        }

        // Configure connection
        config(["database.connections.store_{$store->id}" => [
            'driver' => 'sqlite',
            'database' => $path,
            'prefix' => '',
        ]]);

        // Switch to store connection
        DB::setDefaultConnection("store_{$store->id}");

        // Run store migrations
        $this->runStoreMigrations();

        // Switch back to default connection
        DB::setDefaultConnection(config('database.default'));
    }

    public function deleteStoreDatabase(StoreSilo $store): void
    {
        $path = $this->getDatabasePath($store);
        
        if (File::exists($path)) {
            File::delete($path);
        }
    }

    public function getDatabasePath(StoreSilo $store): string
    {
        return storage_path("store-databases/store_{$store->id}.sqlite");
    }

    private function runStoreMigrations(): void
    {
        $migrationPath = database_path('migrations/store');
        
        // Run migrations from the store migrations directory
        Artisan::call('migrate', [
            '--path' => str_replace(base_path() . '/', '', $migrationPath),
            '--force' => true
        ]);
    }
}
