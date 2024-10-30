<?php

namespace Tests\Feature\Store\Data;

use App\Models\StoreSilo;
use App\Models\Store\Product;
use App\Models\Store\Order;
use App\Services\StoreIsolation\DatabaseManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreMigrationTest extends TestCase
{
    use RefreshDatabase;

    private DatabaseManager $dbManager;
    private StoreConnection $connection;
    private StoreSilo $sourceStore;
    private StoreSilo $targetStore;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dbManager = app(DatabaseManager::class);
        $this->connection = app(StoreConnection::class);
        
        $this->sourceStore = StoreSilo::factory()->create();
        $this->targetStore = StoreSilo::factory()->create();
        
        $this->dbManager->createStoreDatabase($this->sourceStore);
        $this->dbManager->createStoreDatabase($this->targetStore);
    }

    /** @test */
    public function it_migrates_store_data_in_isolation()
    {
        // Create source data
        $this->connection->connect($this->sourceStore);
        $product = Product::create([
            'name' => 'Source Product',
            'price' => 10.00
        ]);

        // Migrate to target
        $this->dbManager->migrateStore($this->sourceStore, $this->targetStore);
        
        // Check target data
        $this->connection->connect($this->targetStore);
        $this->assertDatabaseHas('products', [
            'name' => 'Source Product'
        ], $this->dbManager->getConnectionName($this->targetStore));
    }

    /** @test */
    public function it_preserves_data_relationships_during_migration()
    {
        // Create related data in source
        $this->connection->connect($this->sourceStore);
        $product = Product::create([
            'name' => 'Source Product',
            'price' => 10.00
        ]);
        
        $order = Order::create([
            'product_id' => $product->id,
            'order_number' => 'ORD-1',
            'amount' => 10.00,
            'status' => 'paid',
            'customer_details' => ['email' => 'test@example.com']
        ]);

        // Migrate to target
        $this->dbManager->migrateStore($this->sourceStore, $this->targetStore);
        
        // Check relationships in target
        $this->connection->connect($this->targetStore);
        $migratedOrder = Order::first();
        $this->assertEquals(
            'Source Product',
            $migratedOrder->product->name
        );
    }

    /** @test */
    public function it_prevents_data_contamination_during_migration()
    {
        // Create data in both stores
        $this->connection->connect($this->sourceStore);
        Product::create([
            'name' => 'Source Product',
            'price' => 10.00
        ]);

        $this->connection->connect($this->targetStore);
        Product::create([
            'name' => 'Target Product',
            'price' => 20.00
        ]);

        // Migrate
        $this->dbManager->migrateStore($this->sourceStore, $this->targetStore);
        
        // Verify source data remains isolated
        $this->connection->connect($this->sourceStore);
        $this->assertDatabaseMissing('products', [
            'name' => 'Target Product'
        ], $this->dbManager->getConnectionName($this->sourceStore));
    }

    /** @test */
    public function it_handles_schema_differences_during_migration()
    {
        // Add custom column to source
        $this->connection->connect($this->sourceStore);
        $this->dbManager->addColumn(
            $this->sourceStore,
            'products',
            'custom_field'
        );

        Product::create([
            'name' => 'Source Product',
            'price' => 10.00,
            'custom_field' => 'test'
        ]);

        // Migrate to target (should handle missing column)
        $this->dbManager->migrateStore($this->sourceStore, $this->targetStore);
        
        $this->connection->connect($this->targetStore);
        $this->assertDatabaseHas('products', [
            'name' => 'Source Product'
        ], $this->dbManager->getConnectionName($this->targetStore));
    }
}
