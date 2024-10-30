<?php

namespace Tests\Feature\Integration\Billing;

use App\Models\StoreSilo;
use App\Models\Store\Product;
use App\Models\Store\Order;
use App\Services\StoreIsolation\DatabaseManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomBillingTest extends TestCase
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
            'subscription_tier' => 'enterprise',
            'billing_type' => 'custom',
            'custom_billing' => [
                'base_fee' => 2499.99,
                'transaction_fee' => 0.01, // 1%
                'storage_fee_per_gb' => 0.50,
                'minimum_monthly' => 1999.99,
                'billing_cycle' => 'quarterly'
            ]
        ]);
        
        $this->dbManager->createStoreDatabase($this->store);
        $this->connection->connect($this->store);
    }

    /** @test */
    public function it_calculates_custom_transaction_fees()
    {
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 1000.00
        ]);

        Order::create([
            'product_id' => $product->id,
            'order_number' => 'ORD-1',
            'amount' => 1000.00,
            'status' => 'paid',
            'paid_at' => now(),
            'customer_details' => ['email' => 'test@example.com']
        ]);

        $fees = $this->store->calculateTransactionFees();
        
        $this->assertEquals(10.00, $fees); // 1% of 1000.00
    }

    /** @test */
    public function it_enforces_minimum_monthly_billing()
    {
        $bill = $this->store->generateBill();
        
        $this->assertGreaterThanOrEqual(
            1999.99,
            $bill['total']
        );
    }

    /** @test */
    public function it_handles_quarterly_billing_cycle()
    {
        // Create some activity
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 1000.00
        ]);

        for ($i = 0; $i < 3; $i++) {
            Order::create([
                'product_id' => $product->id,
                'order_number' => "ORD-{$i}",
                'amount' => 1000.00,
                'status' => 'paid',
                'paid_at' => now()->addMonths($i),
                'customer_details' => ['email' => 'test@example.com']
            ]);
        }

        $bill = $this->store->generateQuarterlyBill();
        
        $this->assertEquals(3, $bill['months_included']);
        $this->assertEquals(7499.97, $bill['base_fees']); // 2499.99 * 3
        $this->assertEquals(30.00, $bill['transaction_fees']); // 10.00 * 3
    }

    /** @test */
    public function it_calculates_storage_fees()
    {
        $file = \Illuminate\Http\UploadedFile::fake()->create('large.zip', 5000); // 5GB

        Product::create([
            'name' => 'Large Product',
            'price' => 10.00,
            'files' => [[
                'path' => $file->store('products', 'store_files'),
                'size' => $file->getSize(),
                'name' => 'large.zip'
            ]]
        ]);

        $storageFees = $this->store->calculateStorageFees();
        
        $this->assertEquals(2.50, $storageFees); // 5GB * $0.50
    }

    /** @test */
    public function it_tracks_usage_for_custom_billing()
    {
        $metrics = $this->store->getUsageMetrics();
        
        $this->assertArrayHasKey('storage_gb', $metrics);
        $this->assertArrayHasKey('transaction_volume', $metrics);
        $this->assertArrayHasKey('api_calls', $metrics);
        $this->assertArrayHasKey('bandwidth_gb', $metrics);
    }
}
