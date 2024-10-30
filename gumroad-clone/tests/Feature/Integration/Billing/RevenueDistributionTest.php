<?php

namespace Tests\Feature\Integration\Billing;

use App\Models\StoreSilo;
use App\Models\Store\Product;
use App\Models\Store\Order;
use App\Models\SiloTransaction;
use App\Services\StoreIsolation\DatabaseManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueDistributionTest extends TestCase
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
            'revenue_share_percentage' => 5, // 5% to admin
            'monthly_fee' => 29.99
        ]);
        
        $this->dbManager->createStoreDatabase($this->store);
        $this->connection->connect($this->store);
    }

    /** @test */
    public function it_calculates_admin_revenue_share()
    {
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 1000.00
        ]);

        $order = Order::create([
            'product_id' => $product->id,
            'order_number' => 'ORD-1',
            'amount' => 1000.00,
            'status' => 'paid',
            'paid_at' => now(),
            'customer_details' => ['email' => 'test@example.com']
        ]);

        // Admin should receive 5% of 1000.00 = 50.00
        $this->assertEquals(
            50.00,
            $this->store->calculateAdminShare($order->amount)
        );
    }

    /** @test */
    public function it_distributes_revenue_on_successful_payment()
    {
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 1000.00
        ]);

        $order = Order::create([
            'product_id' => $product->id,
            'order_number' => 'ORD-1',
            'amount' => 1000.00,
            'status' => 'paid',
            'paid_at' => now(),
            'customer_details' => ['email' => 'test@example.com']
        ]);

        $this->assertDatabaseHas('silo_transactions', [
            'store_silo_id' => $this->store->id,
            'transaction_id' => $order->order_number,
            'amount' => 950.00, // 95% to store
            'type' => 'store_share'
        ]);

        $this->assertDatabaseHas('silo_transactions', [
            'store_silo_id' => $this->store->id,
            'transaction_id' => $order->order_number,
            'amount' => 50.00, // 5% to admin
            'type' => 'admin_share'
        ]);
    }

    /** @test */
    public function it_handles_subscription_fees()
    {
        // Fast forward to billing date
        $this->store->update(['next_billing_date' => now()]);
        
        $this->store->processSubscriptionFee();

        $this->assertDatabaseHas('silo_transactions', [
            'store_silo_id' => $this->store->id,
            'amount' => 29.99,
            'type' => 'subscription_fee'
        ]);
    }

    /** @test */
    public function it_adjusts_revenue_share_for_enterprise_tier()
    {
        $this->store->update([
            'subscription_tier' => 'enterprise',
            'revenue_share_percentage' => 3 // 3% for enterprise
        ]);

        $product = Product::create([
            'name' => 'Test Product',
            'price' => 1000.00
        ]);

        $order = Order::create([
            'product_id' => $product->id,
            'order_number' => 'ORD-1',
            'amount' => 1000.00,
            'status' => 'paid',
            'paid_at' => now(),
            'customer_details' => ['email' => 'test@example.com']
        ]);

        $this->assertDatabaseHas('silo_transactions', [
            'store_silo_id' => $this->store->id,
            'transaction_id' => $order->order_number,
            'amount' => 970.00, // 97% to store
            'type' => 'store_share'
        ]);
    }

    /** @test */
    public function it_processes_refund_revenue_adjustments()
    {
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 1000.00
        ]);

        $order = Order::create([
            'product_id' => $product->id,
            'order_number' => 'ORD-1',
            'amount' => 1000.00,
            'status' => 'paid',
            'paid_at' => now(),
            'customer_details' => ['email' => 'test@example.com']
        ]);

        // Process refund
        $order->update(['status' => 'refunded']);

        // Should create reversal transactions
        $this->assertDatabaseHas('silo_transactions', [
            'store_silo_id' => $this->store->id,
            'transaction_id' => $order->order_number,
            'amount' => -950.00,
            'type' => 'store_share_reversal'
        ]);

        $this->assertDatabaseHas('silo_transactions', [
            'store_silo_id' => $this->store->id,
            'transaction_id' => $order->order_number,
            'amount' => -50.00,
            'type' => 'admin_share_reversal'
        ]);
    }
}
