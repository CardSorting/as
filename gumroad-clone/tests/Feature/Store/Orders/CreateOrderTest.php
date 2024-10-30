<?php

namespace Tests\Feature\Store\Orders;

use App\Models\StoreSilo;
use App\Models\Store\Product;
use App\Models\Store\Order;
use App\Services\StoreIsolation\DatabaseManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateOrderTest extends TestCase
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
    public function it_creates_basic_order()
    {
        $order = Order::create([
            'product_id' => $this->product->id,
            'order_number' => 'ORD-' . uniqid(),
            'amount' => $this->product->price,
            'status' => 'pending',
            'customer_details' => [
                'email' => 'test@example.com'
            ]
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'product_id' => $this->product->id,
            'status' => 'pending'
        ], $this->dbManager->getConnectionName($this->store));
    }

    /** @test */
    public function it_generates_unique_order_number()
    {
        $order1 = Order::create([
            'product_id' => $this->product->id,
            'amount' => $this->product->price,
            'status' => 'pending',
            'customer_details' => ['email' => 'test@example.com']
        ]);

        $order2 = Order::create([
            'product_id' => $this->product->id,
            'amount' => $this->product->price,
            'status' => 'pending',
            'customer_details' => ['email' => 'test@example.com']
        ]);

        $this->assertNotEquals($order1->order_number, $order2->order_number);
    }

    /** @test */
    public function it_validates_customer_email()
    {
        $this->expectException(\InvalidArgumentException::class);

        Order::create([
            'product_id' => $this->product->id,
            'amount' => $this->product->price,
            'status' => 'pending',
            'customer_details' => [
                'email' => 'invalid-email'
            ]
        ]);
    }

    /** @test */
    public function it_requires_valid_product()
    {
        $this->expectException(\InvalidArgumentException::class);

        Order::create([
            'product_id' => 999999,
            'amount' => 10.00,
            'status' => 'pending',
            'customer_details' => ['email' => 'test@example.com']
        ]);
    }
}
