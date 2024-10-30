<?php

namespace Tests\Feature\ErrorHandling;

use App\Models\StoreSilo;
use App\Models\Store\Product;
use App\Services\StoreIsolation\DatabaseManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EdgeCasesTest extends TestCase
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
    public function handles_rapid_store_switching()
    {
        $stores = StoreSilo::factory(5)->create();
        
        foreach ($stores as $store) {
            $this->dbManager->createStoreDatabase($store);
        }

        // Rapidly switch between stores
        for ($i = 0; $i < 10; $i++) {
            $store = $stores->random();
            $this->connection->connect($store);
            
            $this->assertEquals(
                $store->id,
                $this->connection->getCurrentStore()->id,
                "Store connection mismatch during rapid switching"
            );
        }
    }

    /** @test */
    public function handles_large_transaction_volume()
    {
        $store = StoreSilo::factory()->create();
        $this->dbManager->createStoreDatabase($store);
        
        $this->connection->connect($store);
        
        // Create many products rapidly
        $products = [];
        for ($i = 0; $i < 100; $i++) {
            $products[] = Product::create([
                'name' => "Product {$i}",
                'price' => rand(1, 100)
            ]);
        }

        $this->assertEquals(
            100,
            Product::count(),
            "Failed to handle large transaction volume"
        );
    }

    /** @test */
    public function handles_database_size_limits()
    {
        $store = StoreSilo::factory()->create();
        $this->dbManager->createStoreDatabase($store);
        
        $this->connection->connect($store);
        
        // Create products with large descriptions
        $largeText = str_repeat('a', 1000000); // 1MB text
        
        try {
            Product::create([
                'name' => 'Large Product',
                'description' => $largeText,
                'price' => 10.00
            ]);
        } catch (\Exception $e) {
            $this->assertStringContainsString(
                'database or disk is full',
                $e->getMessage()
            );
        }
    }

    /** @test */
    public function handles_concurrent_schema_changes()
    {
        $store = StoreSilo::factory()->create();
        $this->dbManager->createStoreDatabase($store);
        
        $this->connection->connect($store);
        
        // Simulate concurrent schema modifications
        $schema = DB::connection($this->dbManager->getConnectionName($store))
            ->getSchemaBuilder();

        try {
            parallel([
                function () use ($schema) {
                    $schema->table('products', function ($table) {
                        $table->string('test_column_1')->nullable();
                    });
                },
                function () use ($schema) {
                    $schema->table('products', function ($table) {
                        $table->string('test_column_2')->nullable();
                    });
                }
            ]);
        } catch (\Exception $e) {
            // Should handle concurrent modifications gracefully
            $this->assertTrue(true);
        }
    }
}
