<?php

namespace Tests\Feature\Store\Orders;

use App\Models\StoreSilo;
use App\Models\Store\Product;
use App\Models\Store\Order;
use App\Services\StoreIsolation\DatabaseManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTransactionTest extends TestCase
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
    public function it_creates_silo_transaction_on_payment()
    {
        $this->order->update([
            'status' => 'paid',
            'paid_at' => now()
        ]);

        // Check parent silo for transaction
        $this->assertDatabaseHas('silo_transactions', [
            'store_silo_id' => $this->store->id,
            'transaction_id' => $this->order->order_number,
            'amount' => $this->order->amount
        ]);
    }

    /** @test */
    public function it_maintains_transaction_isolation()
    {
        // Create another store
        $store2 = StoreSilo::factory()->create();
        $this->dbManager->createStoreDatabase($store2);

        $this->order->update([
            'status' => 'paid',
            'paid_at' => now()
        ]);

        // Verify transaction only exists for correct store
        $this->assertDatabaseMissing('silo_transactions', [
            'store_silo_id' => $store2->id,
            'transaction_id' => $this->order->order_number
        ]);
    }

    /** @test */
    public function it_prevents_duplicate_transactions()
    {
        $this->order->update([
            'status' => 'paid',
            'paid_at' => now()
        ]);

        $this->expectException(\RuntimeException::class);

        // Attempt to create duplicate transaction
        $this->order->update([
            'status' => 'paid',
            'paid_at' => now()
        ]);
    }

    /** @test */
    public function it_records_transaction_timestamp()
    {
        $paidAt = now();
        
        $this->order->update([
            'status' => 'paid',
            'paid_at' => $paidAt
        ]);

        $this->assertDatabaseHas('silo_transactions', [
            'store_silo_id' => $this->store->id,
            'transaction_id' => $this->order->order_number,
            'transaction_date' => $paidAt->toDateTimeString()
        ]);
    }
}
