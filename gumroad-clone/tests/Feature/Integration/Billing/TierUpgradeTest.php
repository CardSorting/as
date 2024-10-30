<?php

namespace Tests\Feature\Integration\Billing;

use App\Models\StoreSilo;
use App\Models\Store\Product;
use App\Models\Store\Order;
use App\Services\StoreIsolation\DatabaseManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TierUpgradeTest extends TestCase
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
    public function it_forces_upgrade_on_revenue_threshold()
    {
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 10000.00
        ]);

        // Create orders exceeding basic tier limit
        for ($i = 0; $i < 6; $i++) {
            Order::create([
                'product_id' => $product->id,
                'order_number' => "ORD-{$i}",
                'amount' => 10000.00,
                'status' => 'paid',
                'paid_at' => now(),
                'customer_details' => ['email' => 'test@example.com']
            ]);
        }

        // Should automatically upgrade to professional
        $this->assertEquals(
            'professional',
            $this->store->fresh()->subscription_tier
        );
    }

    /** @test */
    public function it_forces_upgrade_on_storage_threshold()
    {
        $file = \Illuminate\Http\UploadedFile::fake()->create('large.pdf', 1200); // 1.2GB

        // This should trigger automatic upgrade
        Product::create([
            'name' => 'Large Digital Product',
            'price' => 10.00,
            'files' => [[
                'path' => $file->store('products', 'store_files'),
                'size' => $file->getSize(),
                'name' => 'large.pdf'
            ]]
        ]);

        $this->assertEquals(
            'professional',
            $this->store->fresh()->subscription_tier
        );
    }

    /** @test */
    public function it_forces_upgrade_on_product_threshold()
    {
        // Create products up to basic limit
        for ($i = 0; $i < 10; $i++) {
            Product::create([
                'name' => "Product {$i}",
                'price' => 10.00
            ]);
        }

        // This should trigger automatic upgrade
        Product::create([
            'name' => 'Extra Product',
            'price' => 10.00
        ]);

        $this->assertEquals(
            'professional',
            $this->store->fresh()->subscription_tier
        );
    }

    /** @test */
    public function it_applies_new_limits_immediately_after_upgrade()
    {
        // Force upgrade through revenue
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 60000.00
        ]);

        Order::create([
            'product_id' => $product->id,
            'order_number' => 'ORD-1',
            'amount' => 60000.00,
            'status' => 'paid',
            'paid_at' => now(),
            'customer_details' => ['email' => 'test@example.com']
        ]);

        // Verify new limits are available immediately
        $this->assertEquals(50, $this->store->fresh()->getCurrentLimits()['products']);
        $this->assertEquals(5000, $this->store->fresh()->getCurrentLimits()['storage_mb']);
        $this->assertEquals(200000.00, $this->store->fresh()->getCurrentLimits()['monthly_revenue_cap']);
    }

    /** @test */
    public function it_bills_immediately_for_forced_upgrades()
    {
        // Force upgrade through product count
        for ($i = 0; $i < 11; $i++) {
            Product::create([
                'name' => "Product {$i}",
                'price' => 10.00
            ]);
        }

        $bill = $this->store->fresh()->getCurrentBill();

        // Should include immediate charge for professional tier
        $this->assertEquals(99.99, $bill['subscription_fee']);
        $this->assertTrue($bill['immediate_charge']);
    }

    /** @test */
    public function it_prevents_operations_until_upgrade_payment()
    {
        // Force upgrade situation
        for ($i = 0; $i < 10; $i++) {
            Product::create([
                'name' => "Product {$i}",
                'price' => 10.00
            ]);
        }

        // Simulate failed payment
        $this->store->update(['payment_status' => 'failed']);

        $this->expectException(\RuntimeException::class);

        // Try to create another product
        Product::create([
            'name' => 'Extra Product',
            'price' => 10.00
        ]);
    }
}
