<?php

namespace Tests\Feature\Integration\Billing;

use App\Models\StoreSilo;
use App\Models\Store\Product;
use App\Models\Store\Order;
use App\Services\StoreIsolation\DatabaseManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsageEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private DatabaseManager $dbManager;
    private StoreConnection $connection;
    private StoreSilo $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dbManager = app(DatabaseManager::class);
        $this->connection = app(StoreConnection::class);
        
        $this->store = StoreSilo::factory()->create([
            'subscription_tier' => 'basic',
            'monthly_fee' => 29.99,
            'subscription_limits' => [
                'storage_mb' => 1000,
                'products' => 10,
                'monthly_revenue_cap' => 50000.00
            ]
        ]);
        
        $this->dbManager->createStoreDatabase($this->store);
        $this->connection->connect($this->store);
    }

    /** @test */
    public function it_blocks_operations_on_payment_failure()
    {
        $this->store->update(['payment_status' => 'failed']);

        $this->expectException(\RuntimeException::class);

        Product::create([
            'name' => 'New Product',
            'price' => 10.00
        ]);
    }

    /** @test */
    public function it_prevents_exceeding_tier_limits_without_payment()
    {
        // Fill up to limit
        for ($i = 0; $i < 10; $i++) {
            Product::create([
                'name' => "Product {$i}",
                'price' => 10.00
            ]);
        }

        // Simulate payment method issue
        $this->store->update(['payment_method_valid' => false]);

        $this->expectException(\RuntimeException::class);

        // Try to exceed limit
        Product::create([
            'name' => 'Extra Product',
            'price' => 10.00
        ]);
    }

    /** @test */
    public function it_enforces_minimum_tier_requirements()
    {
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 60000.00
        ]);

        $this->expectException(\RuntimeException::class);

        // Try to process order exceeding basic tier without upgrade
        Order::create([
            'product_id' => $product->id,
            'order_number' => 'ORD-1',
            'amount' => 60000.00,
            'status' => 'pending',
            'customer_details' => ['email' => 'test@example.com']
        ]);
    }

    /** @test */
    public function it_maintains_existing_data_on_payment_failure()
    {
        // Create some products
        for ($i = 0; $i < 5; $i++) {
            Product::create([
                'name' => "Product {$i}",
                'price' => 10.00
            ]);
        }

        // Simulate payment failure
        $this->store->update(['payment_status' => 'failed']);

        // Verify data is still accessible
        $this->assertEquals(5, Product::count());
        $this->assertNotNull(Product::first()->name);
    }

    /** @test */
    public function it_resumes_operations_after_payment_resolution()
    {
        // Simulate payment failure
        $this->store->update(['payment_status' => 'failed']);

        // Resolve payment
        $this->store->update([
            'payment_status' => 'active',
            'last_payment_date' => now()
        ]);

        // Should now be able to create products
        $product = Product::create([
            'name' => 'New Product',
            'price' => 10.00
        ]);

        $this->assertNotNull($product->id);
    }
}
