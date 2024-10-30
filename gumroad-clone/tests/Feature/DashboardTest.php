<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;

class DashboardTest extends TestCase
{
    use RefreshDatabase, WithFaker;

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

        // Create multiple orders for each product
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

        // Verify total sales amount (3 * 29.99 + 2 * 49.99)
        $expectedTotal = (3 * 29.99) + (2 * 49.99);
        $response->assertSee(number_format($expectedTotal, 2));

        // Verify total number of orders
        $response->assertSee('5'); // Total orders

        // Verify average order value
        $expectedAverage = $expectedTotal / 5;
        $response->assertSee(number_format($expectedAverage, 2));
    }

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

        // Create both pending and completed orders
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

    public function test_dashboard_shows_empty_state_for_new_users()
    {
        $user = User::factory()->create();

        $salesResponse = $this->actingAs($user)
                             ->get(route('orders.sales'));
        $purchasesResponse = $this->actingAs($user)
                                 ->get(route('orders.purchases'));

        $salesResponse->assertStatus(200);
        $salesResponse->assertSee('No sales yet');
        $salesResponse->assertSee("You haven't made any sales yet");

        $purchasesResponse->assertStatus(200);
        $purchasesResponse->assertSee('No purchases yet');
        $purchasesResponse->assertSee("You haven't made any purchases yet");
    }

    public function test_dashboard_shows_product_performance()
    {
        $seller = User::factory()->create();
        $products = Product::factory()->count(2)->create([
            'user_id' => $seller->id
        ]);

        // Create different numbers of orders for each product
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
        
        // First product should have more orders
        $this->assertTrue(
            $products[0]->orders()->count() > $products[1]->orders()->count()
        );
    }

    public function test_dashboard_date_range_filtering()
    {
        $seller = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $seller->id]);

        // Create orders with different dates
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

        // Test last 30 days filter
        $response = $this->actingAs($seller)
                        ->get(route('orders.sales', ['period' => '30days']));

        $response->assertStatus(200);
        $this->assertEquals(2, $response->viewData('orders')->count());

        // Test last 7 days filter
        $response = $this->actingAs($seller)
                        ->get(route('orders.sales', ['period' => '7days']));

        $response->assertStatus(200);
        $this->assertEquals(1, $response->viewData('orders')->count());
    }

    public function test_dashboard_revenue_with_refunds()
    {
        $seller = User::factory()->create();
        $product = Product::factory()->create([
            'user_id' => $seller->id,
            'price' => 100.00
        ]);

        // Create completed orders
        Order::factory()->count(3)->create([
            'product_id' => $product->id,
            'amount' => $product->price,
            'status' => 'completed'
        ]);

        // Create refunded order
        Order::factory()->create([
            'product_id' => $product->id,
            'amount' => $product->price,
            'status' => 'refunded'
        ]);

        $response = $this->actingAs($seller)
                        ->get(route('orders.sales'));

        $response->assertStatus(200);
        
        // Should only count completed orders in total revenue
        $expectedRevenue = 300.00; // 3 completed orders * $100
        $response->assertSee(number_format($expectedRevenue, 2));
        
        // Should show refund statistics
        $response->assertSee('Refunded Orders');
        $response->assertSee('1'); // 1 refunded order
    }

    public function test_dashboard_product_analytics()
    {
        $seller = User::factory()->create();
        $product = Product::factory()->create([
            'user_id' => $seller->id,
            'views' => 100
        ]);

        // Create orders to calculate conversion rate
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
        $response->assertSee('5%'); // 5 orders from 100 views = 5% conversion
    }

    public function test_dashboard_sales_trends()
    {
        $seller = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $seller->id]);

        // Create orders across different days
        foreach (range(1, 5) as $daysAgo) {
            Order::factory()->create([
                'product_id' => $product->id,
                'created_at' => Carbon::now()->subDays($daysAgo),
                'status' => 'completed'
            ]);
        }

        // Test daily trend
        $response = $this->actingAs($seller)
                        ->get(route('orders.sales', ['trend' => 'daily']));

        $response->assertStatus(200);
        $response->assertSee('Daily Sales Trend');
        
        // Test weekly trend
        $response = $this->actingAs($seller)
                        ->get(route('orders.sales', ['trend' => 'weekly']));

        $response->assertStatus(200);
        $response->assertSee('Weekly Sales Trend');
    }

    public function test_dashboard_sorting_and_filtering()
    {
        $seller = User::factory()->create();
        $products = Product::factory()->count(3)->create([
            'user_id' => $seller->id
        ]);

        // Create orders with different amounts
        foreach ($products as $index => $product) {
            Order::factory()->create([
                'product_id' => $product->id,
                'amount' => ($index + 1) * 100,
                'status' => 'completed'
            ]);
        }

        // Test sorting by amount (highest first)
        $response = $this->actingAs($seller)
                        ->get(route('orders.sales', ['sort' => 'amount', 'order' => 'desc']));

        $response->assertStatus(200);
        $orders = $response->viewData('orders');
        $this->assertTrue($orders->first()->amount > $orders->last()->amount);

        // Test filtering by price range
        $response = $this->actingAs($seller)
                        ->get(route('orders.sales', ['min_amount' => 150, 'max_amount' => 250]));

        $response->assertStatus(200);
        $this->assertEquals(1, $response->viewData('orders')->count());
    }

    public function test_dashboard_export_functionality()
    {
        $seller = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $seller->id]);
        Order::factory()->count(5)->create([
            'product_id' => $product->id,
            'status' => 'completed'
        ]);

        $response = $this->actingAs($seller)
                        ->get(route('orders.sales', ['export' => 'csv']));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv');
        $response->assertHeader('Content-Disposition', 'attachment; filename=sales.csv');
    }
}
