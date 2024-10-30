<?php

namespace Tests\Feature\Order;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_status_transition()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['is_published' => true]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($user)
                        ->patch(route('orders.update', $order), [
                            'status' => 'completed'
                        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'completed'
        ]);
    }

    public function test_invalid_order_status_transition()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['is_published' => true]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'status' => 'completed'
        ]);

        $response = $this->actingAs($user)
                        ->patch(route('orders.update', $order), [
                            'status' => 'invalid_status'
                        ]);

        $response->assertStatus(422);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'completed'
        ]);
    }
}
