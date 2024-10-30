<?php

namespace Tests\Feature\Store\Analytics;

use App\Models\StoreSilo;
use App\Models\Store\Product;
use App\Models\Store\Order;
use App\Services\StoreIsolation\DatabaseManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private DatabaseManager $dbManager;
    private StoreConnection $connection;
    private StoreSilo $store;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dbManager = app(DatabaseManager::class);
        $this->connection = app(StoreConnection::class);
        
        $this->store = StoreSilo::factory()->create();
        $this->dbManager->createStoreDatabase($this->store);
        $this->connection->connect($this->store);
        
        $this->product = Product::create([
            'name' => 'Test Product',
            'price' => 10.00
        ]);
    }

    /** @test */
    public function it_tracks_customer_lifetime_value()
    {
        $customerEmail = 'customer@example.com';
        
        // Create multiple orders for same customer
        $this->createOrder($customerEmail, 10.00);
        $this->createOrder($customerEmail, 20.00);
        $this->createOrder($customerEmail, 30.00);

        $ltv = Order::getCustomerLifetimeValue($customerEmail);

        $this->assertEquals(60.00, $ltv);
    }

    /** @test */
    public function it_identifies_repeat_customers()
    {
        $customerEmail = 'customer@example.com';
        
        $this->createOrder($customerEmail, 10.00);
        $this->createOrder($customerEmail, 10.00);

        $repeatRate = Order::getRepeatCustomerRate();

        $this->assertEquals(100.0, $repeatRate);
    }

    /** @test */
    public function it_maintains_customer_isolation()
    {
        $customerEmail = 'customer@example.com';
        $this->createOrder($customerEmail, 10.00);

        // Create another store
        $store2 = StoreSilo::factory()->create();
        $this->dbManager->createStoreDatabase($store2);
        $this->connection->connect($store2);
        
        $ltv = Order::getCustomerLifetimeValue($customerEmail);

        $this->assertEquals(0, $ltv);
    }

    /** @test */
    public function it_tracks_customer_acquisition_source()
    {
        $this->createOrder('customer@example.com', 10.00, [
            'source' => 'twitter',
            'campaign' => 'summer_sale'
        ]);

        $sources = Order::getAcquisitionSources();

        $this->assertEquals(1, $sources['twitter']['count']);
        $this->assertEquals(10.00, $sources['twitter']['revenue']);
    }

    private function createOrder($email, $amount, $metadata = [])
    {
        return Order::create([
            'product_id' => $this->product->id,
            'order_number' => 'ORD-' . uniqid(),
            'amount' => $amount,
            'status' => 'paid',
            'paid_at' => now(),
            'customer_details' => array_merge([
                'email' => $email
            ], $metadata)
        ]);
    }
}
