<?php

namespace Tests\Feature\Integration\Billing;

use App\Models\StoreSilo;
use App\Models\Store\Product;
use App\Models\Store\Order;
use App\Services\StoreIsolation\DatabaseManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionTierTest extends TestCase
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
    public function it_enforces_product_limit()
    {
        // Create products up to limit
        for ($i = 0; $i < 10; $i++) {
            Product::create([
                'name' => "Product {$i}",
                'price' => 10.00
            ]);
        }

        $this->expectException(\RuntimeException::class);
        
        // Try to exceed limit
        Product::create([
            'name' => 'Excess Product',
            'price' => 10.00
        ]);
    }

    /** @test */
    public function it_tracks_storage_usage()
    {
        $file = \Illuminate\Http\UploadedFile::fake()->create('large.pdf', 500); // 500MB

        Product::create([
            'name' => 'Digital Product',
            'price' => 10.00,
            'files' => [[
                'path' => $file->store('products', 'store_files'),
                'size' => $file->getSize(),
                'name' => 'large.pdf'
            ]]
        ]);

        $this->assertEquals(
            500,
            $this->store->getCurrentStorageUsage()
        );
    }

    /** @test */
    public function it_prevents_exceeding_storage_limit()
    {
        $file = \Illuminate\Http\UploadedFile::fake()->create('huge.pdf', 1200); // 1.2GB

        $this->expectException(\RuntimeException::class);

        Product::create([
            'name' => 'Large Digital Product',
            'price' => 10.00,
            'files' => [[
                'path' => $file->store('products', 'store_files'),
                'size' => $file->getSize(),
                'name' => 'huge.pdf'
            ]]
        ]);
    }

    /** @test */
    public function it_tracks_monthly_revenue()
    {
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 10000.00
        ]);

        // Create 5 orders to approach revenue cap
        for ($i = 0; $i < 5; $i++) {
            Order::create([
                'product_id' => $product->id,
                'order_number' => "ORD-{$i}",
                'amount' => 10000.00,
                'status' => 'paid',
                'paid_at' => now(),
                'customer_details' => ['email' => 'test@example.com']
            ]);
        }

        $this->assertEquals(
            50000.00,
            $this->store->getCurrentMonthlyRevenue()
        );
    }

    /** @test */
    public function it_prevents_exceeding_revenue_cap()
    {
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 60000.00
        ]);

        $this->expectException(\RuntimeException::class);

        Order::create([
            'product_id' => $product->id,
            'order_number' => 'ORD-1',
            'amount' => 60000.00,
            'status' => 'paid',
            'paid_at' => now(),
            'customer_details' => ['email' => 'test@example.com']
        ]);
    }
}
