<?php

namespace Tests\Feature\Integration\Billing;

use App\Models\StoreSilo;
use App\Models\Store\Product;
use App\Models\Store\Order;
use App\Services\StoreIsolation\DatabaseManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsageTrackingTest extends TestCase
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
            'usage_metrics' => [
                'storage_used' => 0,
                'products_count' => 0,
                'monthly_revenue' => 0.00
            ]
        ]);
        
        $this->dbManager->createStoreDatabase($this->store);
        $this->connection->connect($this->store);
    }

    /** @test */
    public function it_tracks_product_count_accurately()
    {
        // Create products
        Product::create(['name' => 'Product 1', 'price' => 10.00]);
        Product::create(['name' => 'Product 2', 'price' => 20.00]);
        
        // Delete one product
        $product3 = Product::create(['name' => 'Product 3', 'price' => 30.00]);
        $product3->delete();

        $metrics = $this->store->fresh()->usage_metrics;
        $this->assertEquals(2, $metrics['products_count']);
    }

    /** @test */
    public function it_tracks_storage_changes()
    {
        $file1 = \Illuminate\Http\UploadedFile::fake()->create('file1.pdf', 100);
        $file2 = \Illuminate\Http\UploadedFile::fake()->create('file2.pdf', 200);

        $product = Product::create([
            'name' => 'Digital Product',
            'price' => 10.00,
            'files' => [[
                'path' => $file1->store('products', 'store_files'),
                'size' => $file1->getSize(),
                'name' => 'file1.pdf'
            ]]
        ]);

        // Add another file
        $files = $product->files;
        $files[] = [
            'path' => $file2->store('products', 'store_files'),
            'size' => $file2->getSize(),
            'name' => 'file2.pdf'
        ];
        $product->update(['files' => $files]);

        $metrics = $this->store->fresh()->usage_metrics;
        $this->assertEquals(300, $metrics['storage_used']);
    }

    /** @test */
    public function it_tracks_monthly_revenue_reset()
    {
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 1000.00
        ]);

        // Create orders in current month
        for ($i = 0; $i < 3; $i++) {
            Order::create([
                'product_id' => $product->id,
                'order_number' => "ORD-{$i}",
                'amount' => 1000.00,
                'status' => 'paid',
                'paid_at' => now(),
                'customer_details' => ['email' => 'test@example.com']
            ]);
        }

        // Create order in next month
        Order::create([
            'product_id' => $product->id,
            'order_number' => 'ORD-NEXT',
            'amount' => 1000.00,
            'status' => 'paid',
            'paid_at' => now()->addMonth(),
            'customer_details' => ['email' => 'test@example.com']
        ]);

        $metrics = $this->store->fresh()->usage_metrics;
        $this->assertEquals(3000.00, $metrics['monthly_revenue']);
    }

    /** @test */
    public function it_tracks_usage_across_billing_cycles()
    {
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 1000.00
        ]);

        // Create orders across multiple months
        $dates = [
            now()->subMonth(),
            now(),
            now()->addMonth()
        ];

        foreach ($dates as $date) {
            Order::create([
                'product_id' => $product->id,
                'order_number' => "ORD-{$date->month}",
                'amount' => 1000.00,
                'status' => 'paid',
                'paid_at' => $date,
                'customer_details' => ['email' => 'test@example.com']
            ]);
        }

        $usageHistory = $this->store->getUsageHistory();
        
        $this->assertCount(3, $usageHistory);
        $this->assertEquals(1000.00, $usageHistory[now()->format('Y-m')]);
    }
}
