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

class PayoutProcessingTest extends TestCase
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
            'payout_schedule' => 'weekly',
            'minimum_payout_amount' => 100.00,
            'payment_status' => 'active'
        ]);
        
        $this->dbManager->createStoreDatabase($this->store);
        $this->connection->connect($this->store);
    }

    /** @test */
    public function it_processes_scheduled_payouts()
    {
        // Create some revenue
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 200.00
        ]);

        Order::create([
            'product_id' => $product->id,
            'order_number' => 'ORD-1',
            'amount' => 200.00,
            'status' => 'paid',
            'paid_at' => now()->subDays(7),
            'customer_details' => ['email' => 'test@example.com']
        ]);

        // Process payout
        $payout = $this->store->processPayout();

        $this->assertNotNull($payout);
        $this->assertEquals(190.00, $payout->amount); // 95% of 200.00
        $this->assertEquals('processed', $payout->status);
    }

    /** @test */
    public function it_enforces_minimum_payout_amount()
    {
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 50.00
        ]);

        Order::create([
            'product_id' => $product->id,
            'order_number' => 'ORD-1',
            'amount' => 50.00,
            'status' => 'paid',
            'paid_at' => now()->subDays(7),
            'customer_details' => ['email' => 'test@example.com']
        ]);

        $payout = $this->store->processPayout();

        $this->assertNull($payout, 'Payout should not process below minimum amount');
    }

    /** @test */
    public function it_holds_payouts_for_suspended_stores()
    {
        // Create revenue
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 200.00
        ]);

        Order::create([
            'product_id' => $product->id,
            'order_number' => 'ORD-1',
            'amount' => 200.00,
            'status' => 'paid',
            'paid_at' => now()->subDays(7),
            'customer_details' => ['email' => 'test@example.com']
        ]);

        // Suspend store
        $this->store->update(['payment_status' => 'suspended']);

        $payout = $this->store->processPayout();

        $this->assertNull($payout);
        $this->assertEquals(190.00, $this->store->getHeldBalance());
    }

    /** @test */
    public function it_processes_held_payouts_after_reinstatement()
    {
        // Create and hold revenue
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 200.00
        ]);

        Order::create([
            'product_id' => $product->id,
            'order_number' => 'ORD-1',
            'amount' => 200.00,
            'status' => 'paid',
            'paid_at' => now()->subDays(7),
            'customer_details' => ['email' => 'test@example.com']
        ]);

        $this->store->update(['payment_status' => 'suspended']);
        $this->store->processPayout(); // Will be held

        // Reinstate store
        $this->store->update(['payment_status' => 'active']);
        $payout = $this->store->processHeldPayouts();

        $this->assertNotNull($payout);
        $this->assertEquals(190.00, $payout->amount);
    }

    /** @test */
    public function it_tracks_payout_history()
    {
        // Create multiple orders
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 100.00
        ]);

        for ($i = 0; $i < 3; $i++) {
            Order::create([
                'product_id' => $product->id,
                'order_number' => "ORD-{$i}",
                'amount' => 100.00,
                'status' => 'paid',
                'paid_at' => now()->subDays($i * 7),
                'customer_details' => ['email' => 'test@example.com']
            ]);
        }

        // Process payouts over time
        for ($i = 0; $i < 3; $i++) {
            $this->travel(($i + 1) * 7)->days();
            $this->store->processPayout();
        }

        $payoutHistory = $this->store->getPayoutHistory();
        
        $this->assertCount(3, $payoutHistory);
        $this->assertEquals(285.00, $payoutHistory->sum('amount')); // 95% of 300.00
    }

    /** @test */
    public function it_handles_payout_failures()
    {
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 200.00
        ]);

        Order::create([
            'product_id' => $product->id,
            'order_number' => 'ORD-1',
            'amount' => 200.00,
            'status' => 'paid',
            'paid_at' => now()->subDays(7),
            'customer_details' => ['email' => 'test@example.com']
        ]);

        // Simulate payout failure
        $this->store->update(['payout_method_valid' => false]);
        
        $payout = $this->store->processPayout();

        $this->assertEquals('failed', $payout->status);
        $this->assertEquals(190.00, $this->store->getHeldBalance());
    }
}
