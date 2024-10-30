<?php

namespace Tests\Feature\Integration\Billing;

use App\Models\User;
use App\Models\StoreSilo;
use App\Models\Store\Product;
use App\Models\Store\Order;
use App\Models\SiloTransaction;
use App\Services\StoreIsolation\DatabaseManager;
use App\Services\StoreIsolation\StoreConnection;
use App\Jobs\ProcessOrderPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentProcessingTest extends TestCase
{
    use RefreshDatabase;

    private DatabaseManager $dbManager;
    private StoreConnection $connection;
    private StoreSilo $store;
    private Product $product;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Use sync queue
        config(['queue.default' => 'sync']);
        
        // Set default connection to testing
        config(['database.default' => 'testing']);
        
        // Run migrations on testing database
        $this->artisan('migrate:fresh');
        
        // Create admin user first in testing database
        $this->user = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com'
        ]);
        
        // Set up store with revenue share
        $this->store = StoreSilo::create([
            'user_id' => $this->user->id,
            'store_domain' => 'test-store',
            'subscription_tier' => 'basic',
            'payment_status' => 'active',
            'monthly_fee' => 29.99,
            'subscription_limits' => [
                'storage_mb' => 1000,
                'products' => 10,
                'monthly_revenue_cap' => 50000.00
            ],
            'next_billing_date' => now()->addMonth(),
            'revenue_share_percentage' => 5, // 5% to admin
            'available_balance' => 0
        ]);

        $this->dbManager = app(DatabaseManager::class);
        $this->connection = app(StoreConnection::class);
        
        // Create and set up store database
        $this->dbManager->createStoreDatabase($this->store);
        
        // Connect to store database
        $this->connection->connect($this->store);
        
        // Create test product
        $this->product = Product::create([
            'name' => 'Test Product',
            'price' => 100.00,
            'is_digital' => true
        ]);
    }

    /** @test */
    public function it_processes_successful_payment()
    {
        // Create order in store database
        DB::setDefaultConnection("store_{$this->store->id}");
        
        $order = Order::create([
            'product_id' => $this->product->id,
            'order_number' => 'ORD-1',
            'amount' => 100.00,
            'status' => 'pending',
            'customer_details' => ['email' => 'test@example.com']
        ]);
        $order->setStoreSilo($this->store);

        // Process payment
        ProcessOrderPayment::dispatchSync($order, [
            'payment_method' => 'card',
            'transaction_id' => 'txn_123',
            'amount' => 100.00
        ]);

        // Verify order status
        $this->assertEquals('paid', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->paid_at);

        // Switch to testing database to verify transactions
        DB::setDefaultConnection('testing');
        
        // Verify revenue distribution
        $this->assertDatabaseHas('silo_transactions', [
            'store_silo_id' => $this->store->id,
            'transaction_id' => 'ORD-1',
            'amount' => 95.00, // 95% to store
            'type' => 'store_share'
        ]);

        $this->assertDatabaseHas('silo_transactions', [
            'store_silo_id' => $this->store->id,
            'transaction_id' => 'ORD-1',
            'amount' => 5.00, // 5% to admin
            'type' => 'admin_share'
        ]);

        // Verify store balance update
        $this->assertEquals(95.00, $this->store->fresh()->available_balance);
    }

    /** @test */
    public function it_blocks_payments_when_store_suspended()
    {
        // Update store status in testing database
        DB::setDefaultConnection('testing');
        $this->store->update(['payment_status' => 'suspended']);

        // Create order in store database
        DB::setDefaultConnection("store_{$this->store->id}");
        
        $order = Order::create([
            'product_id' => $this->product->id,
            'order_number' => 'ORD-1',
            'amount' => 100.00,
            'status' => 'pending',
            'customer_details' => ['email' => 'test@example.com']
        ]);
        $order->setStoreSilo($this->store);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Store payments are suspended');

        ProcessOrderPayment::dispatchSync($order, [
            'payment_method' => 'card',
            'transaction_id' => 'txn_123',
            'amount' => 100.00
        ]);
    }

    /** @test */
    public function it_handles_payment_failures()
    {
        // Create order in store database
        DB::setDefaultConnection("store_{$this->store->id}");
        
        $order = Order::create([
            'product_id' => $this->product->id,
            'order_number' => 'ORD-1',
            'amount' => 100.00,
            'status' => 'pending',
            'customer_details' => ['email' => 'test@example.com']
        ]);
        $order->setStoreSilo($this->store);

        ProcessOrderPayment::dispatchSync($order, [
            'payment_method' => 'card',
            'error' => 'Card declined',
            'amount' => 100.00
        ]);

        $this->assertEquals('failed', $order->fresh()->status);
        
        // Switch to testing database for transaction checks
        DB::setDefaultConnection('testing');
        
        $this->assertEquals(0, $this->store->fresh()->available_balance);
        $this->assertEquals(0, SiloTransaction::count());
    }

    /** @test */
    public function it_prevents_double_payments()
    {
        // Create order in store database
        DB::setDefaultConnection("store_{$this->store->id}");
        
        $order = Order::create([
            'product_id' => $this->product->id,
            'order_number' => 'ORD-1',
            'amount' => 100.00,
            'status' => 'paid',
            'paid_at' => now(),
            'customer_details' => ['email' => 'test@example.com']
        ]);
        $order->setStoreSilo($this->store);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Order is already paid');

        ProcessOrderPayment::dispatchSync($order, [
            'payment_method' => 'card',
            'transaction_id' => 'txn_123',
            'amount' => 100.00
        ]);
    }

    /** @test */
    public function it_validates_payment_amounts()
    {
        // Create order in store database
        DB::setDefaultConnection("store_{$this->store->id}");
        
        $order = Order::create([
            'product_id' => $this->product->id,
            'order_number' => 'ORD-1',
            'amount' => 100.00,
            'status' => 'pending',
            'customer_details' => ['email' => 'test@example.com']
        ]);
        $order->setStoreSilo($this->store);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Payment amount mismatch');

        ProcessOrderPayment::dispatchSync($order, [
            'payment_method' => 'card',
            'transaction_id' => 'txn_123',
            'amount' => 90.00 // Wrong amount
        ]);
    }

    protected function tearDown(): void
    {
        // Clean up store database
        $this->dbManager->deleteStoreDatabase($this->store);
        
        parent::tearDown();
    }
}
