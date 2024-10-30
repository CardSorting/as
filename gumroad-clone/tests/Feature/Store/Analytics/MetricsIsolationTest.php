<?php

namespace Tests\Feature\Store\Analytics;

use App\Models\StoreSilo;
use App\Models\Store\Product;
use App\Models\Store\Order;
use App\Services\StoreIsolation\DatabaseManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetricsIsolationTest extends TestCase
{
    use RefreshDatabase;

    private DatabaseManager $dbManager;
    private StoreConnection $connection;
    private StoreSilo $store1;
    private StoreSilo $store2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dbManager = app(DatabaseManager::class);
        $this->connection = app(StoreConnection::class);
        
        // Create two stores
        $this->store1 = StoreSilo::factory()->create();
        $this->store2 = StoreSilo::factory()->create();
        
        $this->dbManager->createStoreDatabase($this->store1);
        $this->dbManager->createStoreDatabase($this->store2);
    }

    /** @test */
    public function it_isolates_revenue_metrics()
    {
        // Add revenue to store 1
        $this->connection->connect($this->store1);
        $product1 = Product::create(['name' => 'Product 1', 'price' => 10.00]);
        $this->createOrder($product1, 10.00);

        // Add revenue to store 2
        $this->connection->connect($this->store2);
        $product2 = Product::create(['name' => 'Product 2', 'price' => 20.00]);
        $this->createOrder($product2, 20.00);

        // Check store 1 metrics
        $this->connection->connect($this->store1);
        $this->assertEquals(10.00, Order::getTotalRevenue());

        // Check store 2 metrics
        $this->connection->connect($this->store2);
        $this->assertEquals(20.00, Order::getTotalRevenue());
    }

    /** @test */
    public function it_isolates_customer_metrics()
    {
        $customerEmail = 'customer@example.com';

        // Create customer purchase in store 1
        $this->connection->connect($this->store1);
        $product1 = Product::create(['name' => 'Product 1', 'price' => 10.00]);
        $this->createOrder($product1, 10.00, $customerEmail);

        // Create customer purchase in store 2
        $this->connection->connect($this->store2);
        $product2 = Product::create(['name' => 'Product 2', 'price' => 20.00]);
        $this->createOrder($product2, 20.00, $customerEmail);

        // Check customer metrics are isolated
        $this->assertEquals(20.00, Order::getCustomerLifetimeValue($customerEmail));
    }

    /** @test */
    public function it_isolates_product_metrics()
    {
        // Create product in store 1
        $this->connection->connect($this->store1);
        $product1 = Product::create(['name' => 'Product 1', 'price' => 10.00]);
        $product1->recordPageView();

        // Create product in store 2
        $this->connection->connect($this->store2);
        $product2 = Product::create(['name' => 'Product 1', 'price' => 10.00]);
        $product2->recordPageView();
        $product2->recordPageView();

        $this->assertEquals(1, $product1->getPageViews());
        $this->assertEquals(2, $product2->getPageViews());
    }

    /** @test */
    public function it_isolates_analytics_queries()
    {
        // Add data to both stores
        $this->connection->connect($this->store1);
        $product1 = Product::create(['name' => 'Product 1', 'price' => 10.00]);
        $this->createOrder($product1, 10.00);

        $this->connection->connect($this->store2);
        $product2 = Product::create(['name' => 'Product 2', 'price' => 20.00]);
        $this->createOrder($product2, 20.00);

        // Verify analytics queries only return silo-specific data
        $this->assertEquals(1, Order::count());
        $this->assertEquals(20.00, Order::sum('amount'));
    }

    private function createOrder($product, $amount, $email = 'test@example.com')
    {
        return Order::create([
            'product_id' => $product->id,
            'order_number' => 'ORD-' . uniqid(),
            'amount' => $amount,
            'status' => 'paid',
            'paid_at' => now(),
            'customer_details' => ['email' => $email]
        ]);
    }
}
