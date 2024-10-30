<?php

namespace Tests\Feature\Order;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_orders()
    {
        $response = $this->get(route('orders.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_user_can_view_their_orders()
    {
        $user = User::factory()->create();
        $orders = Order::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
                        ->get(route('orders.index'));

        $response->assertStatus(200);
        $response->assertViewHas('orders');
        foreach ($orders as $order) {
            $response->assertSee($order->product->name);
            $response->assertSee(number_format($order->amount, 2));
        }
    }

    public function test_user_can_view_order_details()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed'
        ]);

        $response = $this->actingAs($user)
                        ->get(route('orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee($order->product->name);
        $response->assertSee(number_format($order->amount, 2));
        $response->assertSee('Completed');
    }

    public function test_user_cannot_view_others_orders()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)
                        ->get(route('orders.show', $order));

        $response->assertStatus(403);
    }

    public function test_seller_can_view_their_product_orders()
    {
        $seller = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $seller->id]);
        $buyer = User::factory()->create();
        $order = Order::factory()->create([
            'product_id' => $product->id,
            'user_id' => $buyer->id
        ]);

        $response = $this->actingAs($seller)
                        ->get(route('orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee($order->product->name);
        $response->assertSee($buyer->name);
    }

    public function test_user_can_view_purchases()
    {
        $user = User::factory()->create();
        $orders = Order::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
                        ->get(route('orders.purchases'));

        $response->assertStatus(200);
        foreach ($orders as $order) {
            $response->assertSee($order->product->name);
            $response->assertSee(number_format($order->amount, 2));
        }
    }

    public function test_user_can_view_sales()
    {
        $seller = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $seller->id]);
        $orders = Order::factory()->count(3)->create(['product_id' => $product->id]);

        $response = $this->actingAs($seller)
                        ->get(route('orders.sales'));

        $response->assertStatus(200);
        foreach ($orders as $order) {
            $response->assertSee($order->product->name);
            $response->assertSee(number_format($order->amount, 2));
        }
    }
}
