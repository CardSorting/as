<?php

namespace Tests\Feature\Dashboard;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_purchase_history()
    {
        $buyer = User::factory()->create();
        $products = Product::factory()->count(3)->create([
            'is_published' => true
        ]);

        foreach ($products as $product) {
            Order::factory()->create([
                'user_id' => $buyer->id,
                'product_id' => $product->id,
                'amount' => $product->price,
                'status' => 'completed'
            ]);
        }

        $response = $this->actingAs($buyer)
                        ->get(route('orders.purchases'));

        $response->assertStatus(200);
        
        foreach ($products as $product) {
            $response->assertSee($product->name);
            $response->assertSee(number_format($product->price, 2));
        }
    }

    public function test_sales_dashboard_shows_pending_and_completed_orders()
    {
        $seller = User::factory()->create();
        $product = Product::factory()->create([
            'user_id' => $seller->id,
            'price' => 29.99
        ]);

        Order::factory()->create([
            'product_id' => $product->id,
            'amount' => $product->price,
            'status' => 'completed'
        ]);
        Order::factory()->create([
            'product_id' => $product->id,
            'amount' => $product->price,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($seller)
                        ->get(route('orders.sales'));

        $response->assertStatus(200);
        $response->assertSee('Completed');
        $response->assertSee('Pending');
    }
}
