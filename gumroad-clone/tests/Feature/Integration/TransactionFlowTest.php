<?php

namespace Tests\Feature\Integration;

use App\Models\StoreSilo;
use App\Models\Store\Product;
use App\Models\Store\Order;
use App\Services\StoreIsolation\DatabaseManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionFlowTest extends TestCase
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
    public function completed_order_creates_silo_transaction()
    {
        $order = Order::create([
            'product_id' => $this->product->id,
            'order_number' => 'ORD-' . uniqid(),
            'amount' => 10.00,
            'status' => 'pending',
            'customer_details' => ['email' => 'test@example.com']
        ]);

        $order->update([
            'status' => 'paid',
            'paid_at' => now()
        ]);

        // Verify transaction in parent silo
        $this->assertDatabaseHas('silo_transactions', [
            'store_silo_id' => $this->store->id,
            'transaction_id' => $order->order_number,
            'amount' => 10.00
        ]);
    }

    /** @test */
    public function silo_balance_updates_with_transactions()
    {
        // Create multiple orders
        $this->createPaidOrder(10.00);
        $this->createPaidOrder(20.00);

        $this->assertEquals(
            30.00,
            $this->store->balance->current_balance
        );
    }

    /** @test */
    public function refunded_order_updates_silo_balance()
    {
        $order = $this->createPaidOrder(10.00);
        
        $order->update([
            'status' => 'refunded',
            'refunded_at' => now()
        ]);

        $this->assertEquals(
            0.00,
            $this->store->fresh()->balance->current_balance
        );
    }

    /** @test */
    public function transactions_maintain_store_isolation()
    {
        // Create order in first store
        $this->createPaidOrder(10.00);

        // Create second store
        $store2 = StoreSilo::factory()->create();
        $this->dbManager->createStoreDatabase($store2);
        $this->connection->connect($store2);
        
        // Create product and order in second store
        $product2 = Product::create([
            'name' => 'Store 2 Product',
            'price' => 20.00
        ]);
        $this->createPaidOrder(20.00, $product2);

        // Verify balances are separate
        $this->assertEquals(10.00, $this->store->fresh()->balance->current_balance);
        $this->assertEquals(20.00, $store2->fresh()->balance->current_balance);
    }

    private function createPaidOrder($amount, $product = null): Order
    {
        $order = Order::create([
            'product_id' => $product ? $product->id : $this->product->id,
            'order_number' => 'ORD-' . uniqid(),
            'amount' => $amount,
            'status' => 'pending',
            'customer_details' => ['email' => 'test@example.com']
        ]);

        $order->update([
            'status' => 'paid',
            'paid_at' => now()
        ]);

        return $order;
    }
}
