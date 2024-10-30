<?php

namespace Tests\Unit\Order;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use InvalidArgumentException;

class OrderValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_requires_valid_user()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Order::factory()->create([
            'user_id' => 999999 // Non-existent user
        ]);
    }

    public function test_order_requires_valid_product()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Order::factory()->create([
            'product_id' => 999999 // Non-existent product
        ]);
    }

    public function test_order_requires_valid_status()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid status: invalid_status');
        
        Order::factory()->create([
            'status' => 'invalid_status'
        ]);
    }

    public function test_order_requires_valid_amount()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Order amount must be numeric.');
        
        Order::factory()->create([
            'amount' => 'not_a_number'
        ]);
    }

    public function test_order_amount_matches_product_price()
    {
        $product = Product::factory()->create(['price' => 99.99]);
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'amount' => 99.99
        ]);

        $this->assertEquals($product->price, $order->amount);
    }

    public function test_order_number_is_unique()
    {
        $order1 = Order::factory()->create();
        $order2 = Order::factory()->create();

        $this->assertNotEquals($order1->order_number, $order2->order_number);
        $this->assertMatchesRegularExpression('/^ORD-\d{8}-\d{4}$/', $order1->order_number);
        $this->assertMatchesRegularExpression('/^ORD-\d{8}-\d{4}$/', $order2->order_number);
    }

    public function test_order_refund_validation()
    {
        $order = Order::factory()->create(['status' => 'pending']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only completed orders can be refunded.');
        
        $order->refund();
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
}
