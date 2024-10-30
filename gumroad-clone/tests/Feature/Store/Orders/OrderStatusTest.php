<?php

namespace Tests\Feature\Store\Orders;

use App\Models\StoreSilo;
use App\Models\Store\Product;
use App\Models\Store\Order;
use App\Services\StoreIsolation\DatabaseManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStatusTest extends TestCase
{
    use RefreshDatabase;

    private DatabaseManager $dbManager;
    private StoreConnection $connection;
    private StoreSilo $store;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dbManager = app(DatabaseManager::class);
        $this->connection = app(StoreConnection::class);
        
        $this->store = StoreSilo::factory()->create();
        $this->dbManager->createStoreDatabase($this->store);
        $this->connection->connect($this->store);
        
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 10.00
        ]);

        $this->order = Order::create([
            'product_id' => $product->id,
            'order_number' => 'ORD-' . uniqid(),
            'amount' => $product->price,
            'status' => 'pending',
            'customer_details' => ['email' => 'test@example.com']
        ]);
    }

    /** @test */
    public function it_updates_order_status()
    {
        $this->order->update(['status' => 'paid']);
        
        $this->assertEquals('paid', $this->order->fresh()->status);
    }

    /** @test */
    public function it_records_paid_timestamp()
    {
        $this->order->update([
            'status' => 'paid',
            'paid_at' => now()
        ]);

        $this->assertNotNull($this->order->fresh()->paid_at);
    }

    /** @test */
    public function it_validates_status_transition()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->order->update(['status' => 'invalid_status']);
    }

    /** @test */
    public function it_prevents_invalid_status_changes()
    {
        $this->order->update([
            'status' => 'paid',
            'paid_at' => now()
        ]);

        $this->expectException(\InvalidArgumentException::class);
        
        $this->order->update(['status' => 'pending']);
    }
}
