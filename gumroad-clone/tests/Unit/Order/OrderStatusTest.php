<?php

namespace Tests\Unit\Order;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use InvalidArgumentException;

class OrderStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_has_valid_status()
    {
        $order = Order::factory()->create(['status' => 'pending']);
        $this->assertEquals('pending', $order->status);

        $order->status = 'completed';
        $order->save();
        $this->assertEquals('completed', $order->fresh()->status);
    }

    public function test_order_status_transition_validation()
    {
        $order = Order::factory()->create(['status' => 'completed']);

        // Cannot transition from completed to pending
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot transition from completed to pending');
        
        $order->status = 'pending';
        $order->save();
    }

    public function test_order_download_validation()
    {
        $pendingOrder = Order::factory()->create(['status' => 'pending']);
        $completedOrder = Order::factory()->create(['status' => 'completed']);
        $refundedOrder = Order::factory()->create(['status' => 'refunded']);

        $this->assertFalse($pendingOrder->canDownload());
        $this->assertTrue($completedOrder->canDownload());
        $this->assertFalse($refundedOrder->canDownload());
    }

    public function test_order_refund_handling()
    {
        $order = Order::factory()->create(['status' => 'completed']);

        $order->refund();

        $this->assertEquals('refunded', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->refunded_at);
    }

    public function test_order_status_label()
    {
        $order = Order::factory()->create(['status' => 'completed']);

        $this->assertEquals('Completed', $order->status_label);
        
        $order->status = 'pending';
        $this->assertEquals('Pending', $order->status_label);
        
        $order->status = 'refunded';
        $this->assertEquals('Refunded', $order->status_label);
    }

    public function test_invalid_status_transition()
    {
        $order = Order::factory()->create(['status' => 'refunded']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot transition from refunded to completed');
        
        $order->status = 'completed';
        $order->save();
    }

    public function test_invalid_status_value()
    {
        $order = Order::factory()->create(['status' => 'pending']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid status: invalid_status');
        
        $order->status = 'invalid_status';
        $order->save();
    }

    public function test_can_transition_to_method()
    {
        $pendingOrder = Order::factory()->create(['status' => 'pending']);
        $completedOrder = Order::factory()->create(['status' => 'completed']);
        $refundedOrder = Order::factory()->create(['status' => 'refunded']);

        // From pending
        $this->assertTrue($pendingOrder->canTransitionTo('completed'));
        $this->assertTrue($pendingOrder->canTransitionTo('refunded'));
        $this->assertFalse($pendingOrder->canTransitionTo('pending'));

        // From completed
        $this->assertTrue($completedOrder->canTransitionTo('refunded'));
        $this->assertFalse($completedOrder->canTransitionTo('pending'));
        $this->assertFalse($completedOrder->canTransitionTo('completed'));

        // From refunded (no transitions allowed)
        $this->assertFalse($refundedOrder->canTransitionTo('pending'));
        $this->assertFalse($refundedOrder->canTransitionTo('completed'));
        $this->assertFalse($refundedOrder->canTransitionTo('refunded'));
    }
}
