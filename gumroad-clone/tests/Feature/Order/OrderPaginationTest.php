<?php

namespace Tests\Feature\Order;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_pagination()
    {
        $seller = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $seller->id]);
        $orders = Order::factory()->count(25)->create(['product_id' => $product->id]);

        $response = $this->actingAs($seller)
                        ->get(route('orders.sales'));

        $response->assertStatus(200);
        $response->assertViewHas('orders');
        $this->assertEquals(20, $response->viewData('orders')->count()); // Assuming 20 per page
        $response->assertSee('Next');
    }

    public function test_purchases_pagination()
    {
        $user = User::factory()->create();
        $orders = Order::factory()->count(25)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
                        ->get(route('orders.purchases'));

        $response->assertStatus(200);
        $response->assertViewHas('orders');
        $this->assertEquals(20, $response->viewData('orders')->count()); // Assuming 20 per page
        $response->assertSee('Next');
    }
}
