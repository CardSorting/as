<?php

namespace Tests\Unit\Order;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use InvalidArgumentException;

class OrderAmountTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_has_amount_as_float()
    {
        $order = Order::factory()->create(['amount' => 29.99]);

        $this->assertIsFloat($order->amount);
        $this->assertEquals(29.99, $order->amount);
    }

    public function test_order_amount_formatting()
    {
        $order = Order::factory()->create(['amount' => 99.99]);

        $this->assertEquals('$99.99', $order->formatted_amount);
        $this->assertEquals(9999, $order->amount_in_cents);
    }

    public function test_order_amount_validation()
    {
        $order = Order::factory()->create(['amount' => 99.99]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Order amount cannot be negative.');
        
        $order->amount = -10.00;
        $order->save();
    }

    public function test_order_scope_by_amount_range()
    {
        Order::factory()->create(['amount' => 50.00]);
        Order::factory()->create(['amount' => 100.00]);
        Order::factory()->create(['amount' => 150.00]);

        $orders = Order::amountRange(75, 125)->get();

        $this->assertEquals(1, $orders->count());
        $this->assertEquals(100.00, $orders->first()->amount);
    }

    public function test_order_amount_must_be_numeric()
    {
        $order = Order::factory()->create(['amount' => 99.99]);

        $this->expectException(InvalidArgumentException::class);
        $order->amount = 'not-a-number';
        $order->save();
    }

    public function test_order_amount_precision()
    {
        $order = Order::factory()->create(['amount' => 99.999]);

        // Should be rounded to 2 decimal places
        $this->assertEquals(100.00, $order->amount);
        $this->assertEquals('$100.00', $order->formatted_amount);
        $this->assertEquals(10000, $order->amount_in_cents);
    }
}
