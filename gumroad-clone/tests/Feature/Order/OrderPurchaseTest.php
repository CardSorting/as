<?php

namespace Tests\Feature\Order;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPurchaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_purchase_product()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['is_published' => true]);

        $response = $this->actingAs($user)
                        ->post(route('orders.store', $product));

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'amount' => $product->price,
            'status' => 'completed'
        ]);
    }

    public function test_cannot_purchase_unpublished_product()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['is_published' => false]);

        $response = $this->actingAs($user)
                        ->post(route('orders.store', $product));

        $response->assertStatus(404);
        $this->assertDatabaseMissing('orders', [
            'user_id' => $user->id,
            'product_id' => $product->id
        ]);
    }

    public function test_cannot_purchase_own_product()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'user_id' => $user->id,
            'is_published' => true
        ]);

        $response = $this->actingAs($user)
                        ->post(route('orders.store', $product));

        $response->assertStatus(403);
        $this->assertDatabaseMissing('orders', [
            'user_id' => $user->id,
            'product_id' => $product->id
        ]);
    }

    public function test_order_amount_must_match_product_price()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'price' => 99.99,
            'is_published' => true
        ]);

        $response = $this->actingAs($user)
                        ->post(route('orders.store', $product), [
                            'amount' => 50.00
                        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('orders', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'amount' => 50.00
        ]);
    }
}
