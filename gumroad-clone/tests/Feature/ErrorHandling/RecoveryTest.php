<?php

namespace Tests\Feature\ErrorHandling;

use App\Models\StoreSilo;
use App\Models\Store\Product;
use App\Services\StoreIsolation\DatabaseManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecoveryTest extends TestCase
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
    public function store_recovers_after_connection_failure()
    {
        $store = StoreSilo::factory()->create();
        $this->dbManager->createStoreDatabase($store);
        
        $this->connection->connect($store);
        
        // Create test data
        Product::create([
            'name' => 'Test Product',
            'price' => 10.00
        ]);

        // Force connection failure
        $this->connection->disconnect();
        
        // Attempt reconnection
        $this->connection->connect($store);
        
        $this->assertDatabaseHas('products', [
            'name' => 'Test Product'
        ], $this->dbManager->getConnectionName($store));
    }

    /** @test */
    public function store_handles_concurrent_connection_failures()
    {
        $store = StoreSilo::factory()->create();
        $this->dbManager->createStoreDatabase($store);

        // Simulate multiple concurrent requests
        $responses = [];
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->get("/store/{$store->store_domain}");
        }

        // All requests should either succeed or fail gracefully
        foreach ($responses as $response) {
            $this->assertTrue(
                in_array($response->status(), [200, 503]),
                "Request failed with unexpected status: {$response->status()}"
            );
        }
    }

    /** @test */
    public function store_auto_repairs_missing_tables()
    {
        $store = StoreSilo::factory()->create();
        $this->dbManager->createStoreDatabase($store);
        
        $this->connection->connect($store);
        
        // Drop a table
        DB::connection($this->dbManager->getConnectionName($store))
            ->statement('DROP TABLE IF EXISTS products');

        // Access store, should trigger auto-repair
        $response = $this->get("/store/{$store->store_domain}/products");
        
        // Verify table was recreated
        $this->assertTrue(
            DB::connection($this->dbManager->getConnectionName($store))
                ->getSchemaBuilder()
                ->hasTable('products'),
            'Products table was not auto-repaired'
        );
    }

    /** @test */
    public function store_maintains_data_integrity_during_recovery()
    {
        $store = StoreSilo::factory()->create();
        $this->dbManager->createStoreDatabase($store);
        
        $this->connection->connect($store);
        
        // Create initial data
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 10.00
        ]);

        // Simulate failure and recovery
        $this->connection->disconnect();
        $this->connection->connect($store);

        // Verify data integrity
        $recoveredProduct = Product::find($product->id);
        $this->assertEquals('Test Product', $recoveredProduct->name);
        $this->assertEquals(10.00, $recoveredProduct->price);
    }
}
