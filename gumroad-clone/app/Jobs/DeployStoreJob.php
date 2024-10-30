<?php

namespace App\Jobs;

use App\Models\StoreSilo;
use App\Services\DNS\DNSManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DeployStoreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $store;
    private $deployPath;
    private $storagePath;
    private $dbPath;

    public function __construct(StoreSilo $store)
    {
        $this->store = $store;
        $this->deployPath = storage_path("store-deployments/{$store->id}");
        $this->storagePath = storage_path("local/store-{$store->id}");
        $this->dbPath = storage_path("store-databases/store_{$store->id}.sqlite");
    }

    public function handle(StoreConnection $connection, DNSManager $dns)
    {
        try {
            Log::info('Starting store deployment', [
                'store_id' => $this->store->id,
                'deploy_path' => $this->deployPath,
                'storage_path' => $this->storagePath,
                'db_path' => $this->dbPath
            ]);

            // Create base directories
            $this->createDirectoryStructure();

            // Initialize store storage
            $this->initializeStorage();

            // Initialize store database
            $this->initializeDatabase();

            // Add store domain to DNS
            $dns->addStoreDomain($this->store->store_domain);

            // Connect to store database
            $connection->connect($this->store);

            Log::info('Store deployment completed', [
                'store_id' => $this->store->id
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Store deployment failed', [
                'store_id' => $this->store->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function createDirectoryStructure(): void
    {
        Log::info('Creating directory structure', [
            'deploy_path' => $this->deployPath,
            'storage_path' => $this->storagePath
        ]);

        // Create base deployment directory
        if (!File::exists($this->deployPath)) {
            File::makeDirectory($this->deployPath, 0755, true);
        }

        // Create required subdirectories
        $directories = [
            'public',
            'storage'
        ];

        foreach ($directories as $dir) {
            $path = "{$this->deployPath}/{$dir}";
            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
            }
        }

        // Create database directory if it doesn't exist
        $dbDir = dirname($this->dbPath);
        if (!File::exists($dbDir)) {
            File::makeDirectory($dbDir, 0755, true);
        }

        Log::info('Directory structure created');
    }

    private function initializeStorage(): void
    {
        Log::info('Initializing store storage', [
            'store_id' => $this->store->id,
            'path' => $this->storagePath
        ]);

        // Create base storage directory
        if (!File::exists($this->storagePath)) {
            File::makeDirectory($this->storagePath, 0755, true);
        }

        // Create store-specific storage directories
        $directories = ['products', 'downloads', 'media'];
        
        foreach ($directories as $dir) {
            $path = "{$this->storagePath}/{$dir}";
            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
            }
            Log::info("Created directory: {$dir}", ['path' => $path]);
        }

        // Set up symbolic link
        $linkPath = "{$this->deployPath}/public/storage";
        if (File::exists($linkPath)) {
            File::delete($linkPath);
        }
        File::link($this->storagePath, $linkPath);

        Log::info('Storage initialization completed', [
            'store_id' => $this->store->id,
            'directories' => $directories
        ]);
    }

    private function initializeDatabase(): void
    {
        Log::info('Initializing store database', [
            'store_id' => $this->store->id,
            'path' => $this->dbPath
        ]);

        // Create SQLite database
        if (!File::exists($this->dbPath)) {
            File::put($this->dbPath, '');
            chmod($this->dbPath, 0644);
        }

        // Configure connection
        config([
            "database.connections.store_{$this->store->id}" => [
                'driver' => 'sqlite',
                'database' => $this->dbPath,
            ]
        ]);

        // Create tables
        Schema::connection("store_{$this->store->id}")->create('products', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('currency')->default('USD');
            $table->boolean('is_digital')->default(true);
            $table->string('file_path')->nullable();
            $table->timestamps();
            $table->index('slug');
            $table->index('created_at');
        });

        Schema::connection("store_{$this->store->id}")->create('orders', function ($table) {
            $table->id();
            $table->foreignId('customer_id');
            $table->foreignId('product_id');
            $table->string('status');
            $table->decimal('amount', 10, 2);
            $table->string('currency');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index('customer_id');
            $table->index('created_at');
        });

        Schema::connection("store_{$this->store->id}")->create('settings', function ($table) {
            $table->string('key')->primary();
            $table->text('value');
            $table->timestamps();
        });

        Schema::connection("store_{$this->store->id}")->create('customers', function ($table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::connection("store_{$this->store->id}")->create('downloads', function ($table) {
            $table->id();
            $table->foreignId('order_id');
            $table->foreignId('product_id');
            $table->string('token')->unique();
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->index(['order_id', 'product_id']);
            $table->index('expires_at');
        });

        // Insert default settings
        DB::connection("store_{$this->store->id}")->table('settings')->insert([
            ['key' => 'store_name', 'value' => $this->store->store_domain],
            ['key' => 'theme', 'value' => 'default'],
            ['key' => 'currency', 'value' => 'USD'],
            ['key' => 'timezone', 'value' => 'UTC'],
            ['key' => 'email_notifications', 'value' => '1'],
            ['key' => 'storage_limit', 'value' => $this->store->subscription_limits['storage_mb']],
            ['key' => 'products_limit', 'value' => $this->store->subscription_limits['products']],
            ['key' => 'revenue_cap', 'value' => $this->store->subscription_limits['monthly_revenue_cap']]
        ]);

        Log::info('Database initialization completed', [
            'store_id' => $this->store->id
        ]);
    }
}
