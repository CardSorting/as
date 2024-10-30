<?php

namespace Tests\Unit\Order;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class OrderScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(); // Reset time
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Reset time
        parent::tearDown();
    }

    public function test_can_get_completed_orders()
    {
        Order::factory()->count(3)->create(['status' => 'completed']);
        Order::factory()->count(2)->create(['status' => 'pending']);
        Order::factory()->create(['status' => 'refunded']);

        $completedOrders = Order::completed()->get();

        $this->assertEquals(3, $completedOrders->count());
        $completedOrders->each(function ($order) {
            $this->assertEquals('completed', $order->status);
        });
    }

    public function test_order_scope_by_date_range()
    {
        // Create orders with different dates
        Order::factory()->create([
            'created_at' => now()->subDays(10)
        ]);
        Order::factory()->create([
            'created_at' => now()->subDays(5)
        ]);
        Order::factory()->create([
            'created_at' => now()
        ]);

        $orders = Order::whereBetween('created_at', [
            now()->subDays(7),
            now()
        ])->get();

        $this->assertEquals(2, $orders->count());
    }

    public function test_order_scope_by_status()
    {
        Order::factory()->count(2)->create(['status' => 'pending']);
        Order::factory()->count(3)->create(['status' => 'completed']);
        Order::factory()->count(1)->create(['status' => 'refunded']);

        $this->assertEquals(2, Order::pending()->count());
        $this->assertEquals(3, Order::completed()->count());
        $this->assertEquals(1, Order::refunded()->count());

        // Test chaining scopes
        $recentCompleted = Order::recent()->completed()->get();
        $this->assertEquals(3, $recentCompleted->count());
        $recentCompleted->each(function ($order) {
            $this->assertEquals('completed', $order->status);
        });
    }

    public function test_order_recent_scope()
    {
        $this->refreshDatabase();
        
        // Set a fixed test date
        $testNow = Carbon::parse('2024-01-15 12:00:00');
        Carbon::setTestNow($testNow);

        // Create orders with specific dates
        Order::factory()->create([
            'created_at' => Carbon::parse('2024-01-15'), // Today
            'status' => 'completed'
        ]);
        Order::factory()->create([
            'created_at' => Carbon::parse('2024-01-10'), // 5 days ago
            'status' => 'completed'
        ]);
        Order::factory()->create([
            'created_at' => Carbon::parse('2024-01-07'), // 8 days ago
            'status' => 'completed'
        ]);

        $recentOrders = Order::recent()->get();
        
        $this->assertEquals(2, $recentOrders->count(), 'Should only include orders from the last 7 days');
        $this->assertTrue(
            $recentOrders->every(function ($order) use ($testNow) {
                return $order->created_at->diffInDays($testNow) <= 7;
            }),
            'All orders should be within 7 days'
        );
    }

    public function test_order_scope_combinations()
    {
        $testNow = now();
        Carbon::setTestNow($testNow);

        // Create various orders
        Order::factory()->create([
            'status' => 'completed',
            'amount' => 100,
            'created_at' => $testNow->copy()->subDays(2)
        ]);
        Order::factory()->create([
            'status' => 'completed',
            'amount' => 200,
            'created_at' => $testNow->copy()->subDays(10)
        ]);
        Order::factory()->create([
            'status' => 'pending',
            'amount' => 150,
            'created_at' => $testNow->copy()->subDays(1)
        ]);

        // Test combining multiple scopes
        $orders = Order::completed()
            ->recent()
            ->amountRange(50, 150)
            ->get();

        $this->assertEquals(1, $orders->count());
        $this->assertEquals(100, $orders->first()->amount);
        $this->assertEquals('completed', $orders->first()->status);
        $this->assertTrue($orders->first()->created_at->diffInDays($testNow) <= 7);
    }

    public function test_empty_scope_results()
    {
        // Test with no orders
        $this->assertEquals(0, Order::completed()->count());
        $this->assertEquals(0, Order::recent()->count());
        $this->assertEquals(0, Order::amountRange(0, 100)->count());

        // Test with orders but no matches
        Order::factory()->create(['status' => 'pending']);
        $this->assertEquals(0, Order::completed()->count());

        Order::factory()->create([
            'created_at' => now()->subDays(10),
            'status' => 'completed'
        ]);
        $this->assertEquals(0, Order::recent()->completed()->count());
    }

    public function test_date_range_scope()
    {
        $testNow = Carbon::parse('2024-01-15');
        Carbon::setTestNow($testNow);

        Order::factory()->create(['created_at' => Carbon::parse('2024-01-01')]);
        Order::factory()->create(['created_at' => Carbon::parse('2024-01-07')]);
        Order::factory()->create(['created_at' => Carbon::parse('2024-01-15')]);

        $orders = Order::dateRange(
            Carbon::parse('2024-01-05'),
            Carbon::parse('2024-01-10')
        )->get();

        $this->assertEquals(1, $orders->count());
        $this->assertEquals('2024-01-07', $orders->first()->created_at->format('Y-m-d'));
    }
}
