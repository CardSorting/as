<?php

namespace Tests\Feature\Dashboard;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_sales_dashboard()
    {
        $seller = User::factory()->create();
        $product1 = Product::factory()->create([
            'user_id' => $seller->id,
            'price' => 29.99
        ]);
        $product2 = Product::factory()->create([
            'user_id' => $seller->id,
            'price' => 49.99
        ]);

        Order::factory()->count(3)->create([
            'product_id' => $product1->id,
            'amount' => $product1->price,
            'status' => 'completed'
        ]);
        Order::factory()->count(2)->create([
            'product_id' => $product2->id,
            'amount' => $product2->price,
            'status' => 'completed'
        ]);

        $response = $this->actingAs($seller)
                        ->get(route('orders.sales'));

        $response->assertStatus(200);
        $response->assertSee('Total Sales');
        $response->assertSee('Total Orders');
        $response->assertSee('Average Order Value');

        $expectedTotal = (3 * 29.99) + (2 * 49.99);
        $response->assertSee(number_format($expectedTotal, 2));
        $response->assertSee('5'); // Total orders
        $expectedAverage = $expectedTotal / 5;
        $response->assertSee(number_format($expectedAverage, 2));
    }

    public function test_dashboard_shows_product_performance()
    {
        $seller = User::factory()->create();
        $products = Product::factory()->count(2)->create([
            'user_id' => $seller->id
        ]);

        Order::factory()->count(3)->create([
            'product_id' => $products[0]->id,
            'status' => 'completed'
        ]);
        Order::factory()->count(1)->create([
            'product_id' => $products[1]->id,
            'status' => 'completed'
        ]);

        $response = $this->actingAs($seller)
                        ->get(route('orders.sales'));

        $response->assertStatus(200);
        $response->assertSee($products[0]->name);
        $response->assertSee($products[1]->name);
        
        $this->assertTrue(
            $products[0]->orders()->count() > $products[1]->orders()->count()
        );
    }

    public function test_dashboard_product_analytics()
    {
        $seller = User::factory()->create();
        $product = Product::factory()->create([
            'user_id' => $seller->id,
            'views' => 100
        ]);

        Order::factory()->count(5)->create([
            'product_id' => $product->id,
            'status' => 'completed'
        ]);

        $response = $this->actingAs($seller)
                        ->get(route('orders.sales'));

        $response->assertStatus(200);
        $response->assertSee('Product Views');
        $response->assertSee('100'); // Total views
        $response->assertSee('Conversion Rate');
        $response->assertSee('5%'); // 5 orders from 100 views = 5%
    }

    public function test_dashboard_revenue_with_refunds()
    {
        $seller = User::factory()->create();
        $product = Product::factory()->create([
            'user_id' => $seller->id,
            'price' => 100.00
        ]);

        Order::factory()->count(3)->create([
            'product_id' => $product->id,
            'amount' => $product->price,
            'status' => 'completed'
        ]);

        Order::factory()->create([
            'product_id' => $product->id,
            'amount' => $product->price,
            'status' => 'refunded'
        ]);

        $response = $this->actingAs($seller)
                        ->get(route('orders.sales'));

        $response->assertStatus(200);
        
        $expectedRevenue = 300.00; // 3 completed orders * $100
        $response->assertSee(number_format($expectedRevenue, 2));
        
        $response->assertSee('Refunded Orders');
        $response->assertSee('1'); // 1 refunded order
    }

    public function test_dashboard_sales_trends()
    {
        $seller = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $seller->id]);

        foreach (range(1, 5) as $daysAgo) {
            Order::factory()->create([
                'product_id' => $product->id,
                'created_at' => Carbon::now()->subDays($daysAgo),
                'status' => 'completed'
            ]);
        }

        $response = $this->actingAs($seller)
                        ->get(route('orders.sales', ['trend' => 'daily']));

        $response->assertStatus(200);
        $response->assertSee('Daily Sales Trend');
        
        $response = $this->actingAs($seller)
                        ->get(route('orders.sales', ['trend' => 'weekly']));

        $response->assertStatus(200);
        $response->assertSee('Weekly Sales Trend');
    }
}
