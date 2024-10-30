<?php

namespace Tests\Feature\Dashboard;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardFilteringTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_date_range_filtering()
    {
        $seller = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $seller->id]);

        Order::factory()->create([
            'product_id' => $product->id,
            'created_at' => Carbon::now()->subDays(10),
            'status' => 'completed'
        ]);
        Order::factory()->create([
            'product_id' => $product->id,
            'created_at' => Carbon::now()->subDays(20),
            'status' => 'completed'
        ]);
        Order::factory()->create([
            'product_id' => $product->id,
            'created_at' => Carbon::now()->subDays(40),
            'status' => 'completed'
        ]);

        $response = $this->actingAs($seller)
                        ->get(route('orders.sales', ['period' => '30days']));

        $response->assertStatus(200);
        $this->assertEquals(2, $response->viewData('orders')->count());

        $response = $this->actingAs($seller)
                        ->get(route('orders.sales', ['period' => '7days']));

        $response->assertStatus(200);
        $this->assertEquals(1, $response->viewData('orders')->count());
    }

    public function test_dashboard_sorting_and_filtering()
    {
        $seller = User::factory()->create();
        $products = Product::factory()->count(3)->create([
            'user_id' => $seller->id
        ]);

        foreach ($products as $index => $product) {
            Order::factory()->create([
                'product_id' => $product->id,
                'amount' => ($index + 1) * 100,
                'status' => 'completed'
            ]);
        }

        $response = $this->actingAs($seller)
                        ->get(route('orders.sales', ['sort' => 'amount', 'order' => 'desc']));

        $response->assertStatus(200);
        $orders = $response->viewData('orders');
        $this->assertTrue($orders->first()->amount > $orders->last()->amount);

        $response = $this->actingAs($seller)
                        ->get(route('orders.sales', ['min_amount' => 150, 'max_amount' => 250]));

        $response->assertStatus(200);
        $this->assertEquals(1, $response->viewData('orders')->count());
    }
}
