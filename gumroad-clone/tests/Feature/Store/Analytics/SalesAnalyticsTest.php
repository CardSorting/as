<?php

namespace Tests\Feature\Store\Analytics;

use App\Models\StoreSilo;
use App\Models\Store\Product;
use App\Models\Store\Order;
use App\Services\StoreIsolation\DatabaseManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesAnalyticsTest extends TestCase
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
    public function it_calculates_daily_revenue()
    {
        // Create orders across different days
        $this->createOrder(now()->subDays(2), 10.00);
        $this->createOrder(now()->subDays(2), 20.00);
        $this->createOrder(now()->subDays(1), 30.00);

        $dailyRevenue = Order::getDailyRevenue(now()->subDays(2));

        $this->assertEquals(30.00, $dailyRevenue);
    }

    /** @test */
    public function it_tracks_product_performance()
    {
        $this->createOrder(now(), 10.00);
        $this->createOrder(now(), 10.00);
        
        $product2 = Product::create([
            'name' => 'Another Product',
            'price' => 20.00
        ]);
        $this->createOrder(now(), 20.00, $product2);

        $performance = Product::getPerformanceMetrics();

        $this->assertEquals(2, $performance[$this->product->id]['sales_count']);
        $this->assertEquals(20.00, $performance[$this->product->id]['total_revenue']);
    }

    /** @test */
    public function it_maintains_analytics_isolation()
    {
        $this->createOrder(now(), 10.00);

        // Create another store
        $store2 = StoreSilo::factory()->create();
        $this->dbManager->createStoreDatabase($store2);
        $this->connection->connect($store2);

        $dailyRevenue = Order::getDailyRevenue(now());
        
        $this->assertEquals(0, $dailyRevenue);
    }

    /** @test */
    public function it_calculates_conversion_rates()
    {
        // Track page views
        $this->product->recordPageView();
        $this->product->recordPageView();
        
        // Create one sale
        $this->createOrder(now(), 10.00);

        $conversionRate = $this->product->getConversionRate();
        
        $this->assertEquals(50.0, $conversionRate);
    }

    private function createOrder($date, $amount, $product = null)
    {
        return Order::create([
            'product_id' => $product ? $product->id : $this->product->id,
            'order_number' => 'ORD-' . uniqid(),
            'amount' => $amount,
            'status' => 'paid',
            'paid_at' => $date,
            'customer_details' => ['email' => 'test@example.com']
        ]);
    }
}
