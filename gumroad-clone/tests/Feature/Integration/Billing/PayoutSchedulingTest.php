<?php

namespace Tests\Feature\Integration\Billing;

use App\Models\StoreSilo;
use App\Models\Store\Product;
use App\Models\Store\Order;
use App\Services\StoreIsolation\DatabaseManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayoutSchedulingTest extends TestCase
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
            'payout_day' => 'monday',
            'payment_status' => 'active'
        ]);
        
        $this->dbManager->createStoreDatabase($this->store);
        $this->connection->connect($this->store);
    }

    /** @test */
    public function it_respects_payout_schedule()
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
            'paid_at' => now()->subDays(2),
            'customer_details' => ['email' => 'test@example.com']
        ]);

        // Try processing before schedule
        $earlyPayout = $this->store->processPayout();
        $this->assertNull($earlyPayout);

        // Fast forward to payout day
        $this->travelTo(now()->next('Monday'));
        
        $scheduledPayout = $this->store->processPayout();
        $this->assertNotNull($scheduledPayout);
    }

    /** @test */
    public function it_handles_different_payout_frequencies()
    {
        $schedules = [
            'daily' => 1,
            'weekly' => 7,
            'biweekly' => 14,
            'monthly' => 30
        ];

        foreach ($schedules as $schedule => $days) {
            $store = StoreSilo::factory()->create([
                'payout_schedule' => $schedule,
                'payment_status' => 'active'
            ]);

            $this->dbManager->createStoreDatabase($store);
            $this->connection->connect($store);

            // Create revenue
            $product = Product::create([
                'name' => 'Test Product',
                'price' => 200.00
            ]);

            Order::create([
                'product_id' => $product->id,
                'order_number' => "ORD-{$schedule}",
                'amount' => 200.00,
                'status' => 'paid',
                'paid_at' => now(),
                'customer_details' => ['email' => 'test@example.com']
            ]);

            // Fast forward to next payout
            $this->travel($days)->days();
            
            $payout = $store->processPayout();
            $this->assertNotNull($payout);
        }
    }

    /** @test */
    public function it_accumulates_revenue_between_payouts()
    {
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 100.00
        ]);

        // Create orders throughout the week
        for ($i = 0; $i < 5; $i++) {
            Order::create([
                'product_id' => $product->id,
                'order_number' => "ORD-{$i}",
                'amount' => 100.00,
                'status' => 'paid',
                'paid_at' => now()->addDays($i),
                'customer_details' => ['email' => 'test@example.com']
            ]);
        }

        // Fast forward to payout day
        $this->travelTo(now()->next('Monday'));
        
        $payout = $this->store->processPayout();
        
        $this->assertEquals(475.00, $payout->amount); // 95% of 500.00
    }

    /** @test */
    public function it_handles_timezone_differences()
    {
        $this->store->update(['timezone' => 'Asia/Tokyo']);

        $product = Product::create([
            'name' => 'Test Product',
            'price' => 200.00
        ]);

        Order::create([
            'product_id' => $product->id,
            'order_number' => 'ORD-1',
            'amount' => 200.00,
            'status' => 'paid',
            'paid_at' => now(),
            'customer_details' => ['email' => 'test@example.com']
        ]);

        // Travel to Monday in store's timezone
        $this->travelTo(now()->timezone('Asia/Tokyo')->next('Monday'));
        
        $payout = $this->store->processPayout();
        $this->assertNotNull($payout);
    }
}
