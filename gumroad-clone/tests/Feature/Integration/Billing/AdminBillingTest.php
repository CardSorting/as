<?php

namespace Tests\Feature\Integration\Billing;

use App\Models\User;
use App\Models\StoreSilo;
use App\Models\Store\Product;
use App\Models\Store\Order;
use App\Services\StoreIsolation\DatabaseManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBillingTest extends TestCase
{
    use RefreshDatabase;

    private DatabaseManager $dbManager;
    private StoreConnection $connection;
    private User $admin;
    private StoreSilo $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dbManager = app(DatabaseManager::class);
        $this->connection = app(StoreConnection::class);
        
        $this->admin = User::factory()->create(['role' => 'admin']);
        
        $this->store = StoreSilo::factory()->create([
            'subscription_tier' => 'basic',
            'monthly_fee' => 29.99,
            'billing_day' => now()->day,
            'next_billing_date' => now()->addMonth()
        ]);
        
        $this->dbManager->createStoreDatabase($this->store);
        $this->connection->connect($this->store);
    }

    /** @test */
    public function it_calculates_monthly_subscription_fees()
    {
        $stores = StoreSilo::factory(3)->create([
            'subscription_tier' => 'basic',
            'monthly_fee' => 29.99,
            'billing_day' => now()->day,
            'next_billing_date' => now()->addMonth()
        ]);

        $totalFees = StoreSilo::calculateMonthlyFees();

        $this->assertEquals(
            29.99 * 4, // 3 new stores + 1 from setUp
            $totalFees
        );
    }

    /** @test */
    public function it_tracks_overages()
    {
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 10000.00
        ]);

        // Create orders exceeding basic tier limit
        for ($i = 0; $i < 6; $i++) {
            Order::create([
                'product_id' => $product->id,
                'order_number' => "ORD-{$i}",
                'amount' => 10000.00,
                'status' => 'paid',
                'paid_at' => now(),
                'customer_details' => ['email' => 'test@example.com']
            ]);
        }

        $overage = $this->store->calculateOverages();
        
        $this->assertEquals(
            10000.00, // Amount exceeding 50000 cap
            $overage['revenue_overage']
        );
    }

    /** @test */
    public function it_applies_tier_upgrades_automatically()
    {
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 10000.00
        ]);

        // Exceed basic tier limits consistently
        for ($i = 0; $i < 6; $i++) {
            Order::create([
                'product_id' => $product->id,
                'order_number' => "ORD-{$i}",
                'amount' => 10000.00,
                'status' => 'paid',
                'paid_at' => now()->subDays($i),
                'customer_details' => ['email' => 'test@example.com']
            ]);
        }

        $this->store->checkAndUpgradeTier();

        $this->assertEquals(
            'professional',
            $this->store->fresh()->subscription_tier
        );
    }

    /** @test */
    public function it_generates_accurate_billing_statements()
    {
        // Add some usage
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 10000.00
        ]);

        for ($i = 0; $i < 6; $i++) {
            Order::create([
                'product_id' => $product->id,
                'order_number' => "ORD-{$i}",
                'amount' => 10000.00,
                'status' => 'paid',
                'paid_at' => now()->subDays($i),
                'customer_details' => ['email' => 'test@example.com']
            ]);
        }

        $statement = $this->store->generateBillingStatement();

        $this->assertEquals(29.99, $statement['base_fee']);
        $this->assertGreaterThan(0, $statement['overage_fees']);
        $this->assertEquals(
            $statement['base_fee'] + $statement['overage_fees'],
            $statement['total_due']
        );
    }
}
