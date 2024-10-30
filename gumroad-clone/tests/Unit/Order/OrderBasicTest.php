<?php

namespace Tests\Unit\Order;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderBasicTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_belongs_to_user()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $order->user);
        $this->assertEquals($user->id, $order->user->id);
    }

    public function test_order_belongs_to_product()
    {
        $product = Product::factory()->create();
        $order = Order::factory()->create(['product_id' => $product->id]);

        $this->assertInstanceOf(Product::class, $order->product);
        $this->assertEquals($product->id, $order->product->id);
    }

    public function test_order_has_payment_id()
    {
        $paymentId = 'demo_' . uniqid();
        $order = Order::factory()->create(['payment_id' => $paymentId]);

        $this->assertEquals($paymentId, $order->payment_id);
    }

    public function test_user_can_access_their_orders()
    {
        $user = User::factory()->create();
        Order::factory()->count(3)->create(['user_id' => $user->id]);

        $this->assertEquals(3, $user->orders()->count());
    }

    public function test_product_can_access_its_orders()
    {
        $product = Product::factory()->create();
        Order::factory()->count(3)->create(['product_id' => $product->id]);

        $this->assertEquals(3, $product->orders()->count());
    }

    public function test_order_date_accessors()
    {
        $order = Order::factory()->create();

        $this->assertInstanceOf(Carbon::class, $order->created_at);
        $this->assertInstanceOf(Carbon::class, $order->updated_at);
        $this->assertEquals(
            $order->created_at->format('Y-m-d'),
            $order->formatted_date
        );
    }

    public function test_order_number_generation()
    {
        $order = Order::factory()->create();

        $this->assertNotNull($order->order_number);
        $this->assertMatchesRegularExpression('/^ORD-\d{8}-\d{4}$/', $order->order_number);
    }
}
