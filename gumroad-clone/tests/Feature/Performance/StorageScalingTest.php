<?php

namespace Tests\Feature\Performance;

use App\Models\StoreSilo;
use App\Models\Store\Product;
use App\Services\StoreIsolation\DatabaseManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorageScalingTest extends TestCase
{
    use RefreshDatabase;

    private DatabaseManager $dbManager;
    private StoreConnection $connection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dbManager = app(DatabaseManager::class);
        $this->connection = app(StoreConnection::class);
    }

    /** @test */
    public function database_size_remains_manageable_with_many_stores()
    {
        $stores = StoreSilo::factory(10)->create();
        $maxSizeKb = 1024; // 1MB per store database

        foreach ($stores as $store) {
            $this->dbManager->createStoreDatabase($store);
            $this->connection->connect($store);

            // Create some sample data
            Product::factory(50)->create();

            $dbSize = filesize($this->dbManager->getDatabasePath($store));
            
            $this->assertLessThan(
                $maxSizeKb * 1024, 
                $dbSize,
                "Store database exceeds size limit: {$dbSize} bytes"
            );
        }
    }

    /** @test */
    public function file_storage_scales_properly()
    {
        $store = StoreSilo::factory()->create();
        $this->dbManager->createStoreDatabase($store);
        
        $storePath = storage_path("store-files/{$store->id}");
        $testFileSize = 1024 * 1024; // 1MB
        
        // Create test files
        for ($i = 0; $i < 10; $i++) {
            $filePath = "{$storePath}/test_{$i}.dat";
            file_put_contents($filePath, random_bytes($testFileSize));
        }

        $totalSize = $this->getDirectorySize($storePath);
        $expectedMaxSize = $testFileSize * 10;

        $this->assertEquals(
            $expectedMaxSize,
            $totalSize,
            "File storage size mismatch"
        );
    }

    /** @test */
    public function connection_pool_handles_multiple_stores()
    {
        $stores = StoreSilo::factory(5)->create();
        $activeConnections = [];

        foreach ($stores as $store) {
            $this->dbManager->createStoreDatabase($store);
            $this->connection->connect($store);
            
            // Verify connection is active
            $this->assertTrue(
                $this->connection->getCurrentStore()->id === $store->id,
                "Connection not properly established for store {$store->id}"
            );
            
            $activeConnections[] = $store->id;
        }

        // Verify we can switch back to any store
        $randomStore = $stores->random();
        $this->connection->connect($randomStore);
        
        $this->assertTrue(
            $this->connection->getCurrentStore()->id === $randomStore->id,
            "Failed to switch back to store {$randomStore->id}"
        );
    }

    /** @test */
    public function concurrent_store_operations_are_isolated()
    {
        $stores = StoreSilo::factory(3)->create();
        
        foreach ($stores as $store) {
            $this->dbManager->createStoreDatabase($store);
        }

        // Simulate concurrent operations
        $results = [];
        foreach ($stores as $store) {
            $this->connection->connect($store);
            
            // Create a product
            $product = Product::create([
                'name' => "Product for store {$store->id}",
                'price' => 10.00
            ]);
            
            $results[$store->id] = $product->id;
        }

        // Verify each store has its own product ID sequence
        $this->assertEquals(
            count(array_unique($results)),
            count($results),
            "Product IDs are not unique across stores"
        );
    }

    private function getDirectorySize(string $path): int
    {
        $size = 0;
        foreach (glob(rtrim($path, '/').'/*', GLOB_NOSORT) as $each) {
            $size += is_file($each) ? filesize($each) : $this->getDirectorySize($each);
        }
        return $size;
    }
}
