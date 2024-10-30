<?php

namespace Tests\Feature\Store\Access;

use App\Models\StoreSilo;
use App\Models\Store\Product;
use App\Models\Store\Order;
use App\Services\StoreIsolation\DatabaseManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAccessTest extends TestCase
{
    use RefreshDatabase;

    private DatabaseManager $dbManager;
    private StoreConnection $connection;
    private StoreSilo $store;
    private Product $product;
    private Order $order;

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

        $this->order = Order::create([
            'product_id' => $this->product->id,
            'order_number' => 'ORD-' . uniqid(),
            'amount' => 10.00,
            'status' => 'paid',
            'paid_at' => now(),
            'customer_details' => ['email' => 'customer@example.com']
        ]);
    }

    /** @test */
    public function customer_can_access_purchase_page()
    {
        $response = $this->get("/store/{$this->store->store_domain}/p/{$this->product->id}");

        $response->assertOk();
    }

    /** @test */
    public function customer_can_access_order_with_valid_token()
    {
        $response = $this->get("/store/{$this->store->store_domain}/orders/{$this->order->id}", [
            'token' => $this->order->access_token
        ]);

        $response->assertOk();
    }

    /** @test */
    public function customer_cannot_access_order_without_token()
    {
        $response = $this->get("/store/{$this->store->store_domain}/orders/{$this->order->id}");

        $response->assertForbidden();
    }

    /** @test */
    public function customer_cannot_access_other_store_orders()
    {
        // Create another store and order
        $store2 = StoreSilo::factory()->create();
        $this->dbManager->createStoreDatabase($store2);
        $this->connection->connect($store2);
        
        $product2 = Product::create([
            'name' => 'Store 2 Product',
            'price' => 20.00
        ]);

        $order2 = Order::create([
            'product_id' => $product2->id,
            'order_number' => 'ORD-' . uniqid(),
            'amount' => 20.00,
            'status' => 'paid',
            'customer_details' => ['email' => 'customer@example.com']
        ]);

        $response = $this->get("/store/{$this->store->store_domain}/orders/{$order2->id}", [
            'token' => $this->order->access_token
        ]);

        $response->assertNotFound();
    }
}
