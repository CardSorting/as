<?php

namespace Tests\Unit\Order;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_statistics()
    {
        // Create orders with different statuses
        Order::factory()->count(3)->create(['status' => 'completed', 'amount' => 100]);
        Order::factory()->count(2)->create(['status' => 'pending', 'amount' => 100]);
        Order::factory()->create(['status' => 'refunded', 'amount' => 100]);

        $stats = Order::statistics();

        $this->assertEquals(6, $stats['total_orders']);
        $this->assertEquals(3, $stats['completed_orders']);
        $this->assertEquals(2, $stats['pending_orders']);
        $this->assertEquals(1, $stats['refunded_orders']);
        $this->assertEquals(300, $stats['total_revenue']);
        $this->assertEquals(100, $stats['average_order_value']);
        $this->assertEquals(6, $stats['recent_orders']); // All orders are recent in test
        $this->assertEquals(16.67, round($stats['refund_rate'], 2)); // 1/6 * 100
    }

    public function test_order_total_by_user()
    {
        // Create users
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Create orders for user1
        Order::factory()->count(2)->create([
            'user_id' => $user1->id,
            'status' => 'completed',
            'amount' => 100
        ]);
        Order::factory()->create([
            'user_id' => $user1->id,
            'status' => 'refunded',
            'amount' => 100
        ]);

        // Create orders for user2
        Order::factory()->create([
            'user_id' => $user2->id,
            'status' => 'completed',
            'amount' => 150
        ]);
        Order::factory()->create([
            'user_id' => $user2->id,
            'status' => 'pending',
            'amount' => 150
        ]);

        $stats = Order::totalByUser();

        // Check user1 stats
        $user1Stats = $stats->firstWhere('user.id', $user1->id);
        $this->assertEquals(3, $user1Stats['total_orders']);
        $this->assertEquals(200, $user1Stats['total_revenue']);
        $this->assertEquals(2, $user1Stats['completed_orders']);
        $this->assertEquals(1, $user1Stats['refunded_orders']);
        $this->assertEquals(100, $user1Stats['average_order_value']);

        // Check user2 stats
        $user2Stats = $stats->firstWhere('user.id', $user2->id);
        $this->assertEquals(2, $user2Stats['total_orders']);
        $this->assertEquals(150, $user2Stats['total_revenue']);
        $this->assertEquals(1, $user2Stats['completed_orders']);
        $this->assertEquals(0, $user2Stats['refunded_orders']);
        $this->assertEquals(150, $user2Stats['average_order_value']);
    }
}
