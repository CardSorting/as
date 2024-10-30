<?php

namespace Tests\Feature\Store\Lifecycle;

use App\Jobs\DeployStoreJob;
use App\Models\StoreSilo;
use App\Models\User;
use App\Services\DNS\DNSManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class StoreInitializationTest extends TestCase
{
    use RefreshDatabase;

    private StoreSilo $store;
    private string $deployPath;
    private string $storagePath;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test store
        $user = User::factory()->create();
        $this->store = StoreSilo::create([
            'user_id' => $user->id,
            'store_domain' => 'test-store',
            'subscription_tier' => 'basic',
            'payment_status' => 'active',
            'monthly_fee' => 29.99,
            'subscription_limits' => [
                'storage_mb' => 1000,
                'products' => 10,
                'monthly_revenue_cap' => 50000.00
            ],
            'next_billing_date' => now()->addMonth(),
            'revenue_share_percentage' => 5.0,
            'available_balance' => 0
        ]);

        $this->deployPath = storage_path("store-deployments/{$this->store->id}");
        $this->storagePath = storage_path("local/store-{$this->store->id}");
        
        // Clean up any existing test directories
        foreach ([$this->deployPath, $this->storagePath] as $path) {
            if (File::exists($path)) {
                File::deleteDirectory($path);
            }
        }
    }

    /** @test */
    public function it_creates_store_database_with_required_tables()
    {
        // Mock dependencies
        $dns = $this->mock(DNSManager::class);
        $dns->shouldReceive('addStoreDomain')->once()->with('test-store');

        $connection = $this->mock(StoreConnection::class);
        $connection->shouldReceive('connect')->once()->with($this->store);

        // Run deployment job
        $job = new DeployStoreJob($this->store);
        $result = $job->handle($connection, $dns);

        // Verify job completed
        $this->assertTrue($result);

        // Verify database exists
        $dbPath = storage_path("store-databases/store_{$this->store->id}.sqlite");
        $this->assertFileExists($dbPath);

        // Connect to store database
        config([
            "database.connections.store_{$this->store->id}" => [
                'driver' => 'sqlite',
                'database' => $dbPath,
            ]
        ]);

        // Verify required tables exist
        $tables = [
            'products',
            'orders',
            'settings',
            'customers',
            'downloads'
        ];

        foreach ($tables as $table) {
            $this->assertTrue(
                DB::connection("store_{$this->store->id}")
                    ->getSchemaBuilder()
                    ->hasTable($table),
                "Table {$table} not found in store database"
            );
        }
    }

    /** @test */
    public function it_initializes_store_with_default_settings()
    {
        // Mock dependencies
        $dns = $this->mock(DNSManager::class);
        $dns->shouldReceive('addStoreDomain')->once()->with('test-store');

        $connection = $this->mock(StoreConnection::class);
        $connection->shouldReceive('connect')->once()->with($this->store);

        // Run deployment job
        $job = new DeployStoreJob($this->store);
        $result = $job->handle($connection, $dns);

        // Verify job completed
        $this->assertTrue($result);

        // Connect to store database
        $dbPath = storage_path("store-databases/store_{$this->store->id}.sqlite");
        config([
            "database.connections.store_{$this->store->id}" => [
                'driver' => 'sqlite',
                'database' => $dbPath,
            ]
        ]);

        // Verify default settings
        $settings = DB::connection("store_{$this->store->id}")
            ->table('settings')
            ->get()
            ->keyBy('key')
            ->map(fn($item) => $item->value)
            ->toArray();

        $expectedSettings = [
            'store_name' => 'test-store',
            'theme' => 'default',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'email_notifications' => true,
            'storage_limit' => 1000,
            'products_limit' => 10,
            'revenue_cap' => 50000.00
        ];

        foreach ($expectedSettings as $key => $value) {
            $this->assertArrayHasKey($key, $settings, "Setting {$key} not found");
            $this->assertEquals($value, $settings[$key], "Setting {$key} has wrong value");
        }
    }

    /** @test */
    public function it_creates_required_database_indexes()
    {
        // Mock dependencies
        $dns = $this->mock(DNSManager::class);
        $dns->shouldReceive('addStoreDomain')->once()->with('test-store');

        $connection = $this->mock(StoreConnection::class);
        $connection->shouldReceive('connect')->once()->with($this->store);

        // Run deployment job
        $job = new DeployStoreJob($this->store);
        $result = $job->handle($connection, $dns);

        // Verify job completed
        $this->assertTrue($result);

        // Connect to store database
        $dbPath = storage_path("store-databases/store_{$this->store->id}.sqlite");
        config([
            "database.connections.store_{$this->store->id}" => [
                'driver' => 'sqlite',
                'database' => $dbPath,
            ]
        ]);

        // Verify indexes exist
        $expectedIndexes = [
            'products' => ['slug', 'created_at'],
            'orders' => ['customer_id', 'created_at'],
            'downloads' => ['order_id', 'product_id', 'expires_at']
        ];

        foreach ($expectedIndexes as $table => $columns) {
            foreach ($columns as $column) {
                $this->assertTrue(
                    $this->indexExists($table, $column),
                    "Index on {$table}.{$column} not found"
                );
            }
        }
    }

    private function indexExists(string $table, string $column): bool
    {
        $indexes = DB::connection("store_{$this->store->id}")
            ->select("SELECT * FROM sqlite_master WHERE type = 'index' AND tbl_name = ? AND sql LIKE ?", [
                $table,
                "%{$column}%"
            ]);

        return count($indexes) > 0;
    }

    protected function tearDown(): void
    {
        // Clean up test directories
        foreach ([$this->deployPath, $this->storagePath] as $path) {
            if (File::exists($path)) {
                File::deleteDirectory($path);
            }
        }

        // Clean up test database
        $dbPath = storage_path("store-databases/store_{$this->store->id}.sqlite");
        if (File::exists($dbPath)) {
            File::delete($dbPath);
        }

        parent::tearDown();
    }
}
