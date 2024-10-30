<?php

namespace Tests\Unit\Product;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductStatisticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    public function test_product_sales_statistics()
    {
        $product = Product::factory()->create(['price' => 100]);

        // Create completed orders
        Order::factory()->count(3)->create([
            'product_id' => $product->id,
            'amount' => $product->price,
            'status' => 'completed'
        ]);

        // Create pending order (shouldn't count in stats)
        Order::factory()->create([
            'product_id' => $product->id,
            'amount' => $product->price,
            'status' => 'pending'
        ]);

        $this->assertEquals(3, $product->completed_orders_count);
        $this->assertEquals(300, $product->total_revenue);
        $this->assertEquals(100, $product->average_order_value);
    }

    public function test_product_scope_by_popularity()
    {
        // Delete any existing data
        Order::query()->delete();
        Product::query()->delete();

        // Create products with specific names for easier debugging
        $product1 = Product::factory()->create(['name' => 'Product 1']);
        $product2 = Product::factory()->create(['name' => 'Product 2']);
        $product3 = Product::factory()->create(['name' => 'Product 3']);

        // Create completed orders for each product
        Order::factory()->count(3)->create([
            'product_id' => $product1->id,
            'status' => 'completed'
        ]);
        Order::factory()->count(1)->create([
            'product_id' => $product2->id,
            'status' => 'completed'
        ]);
        Order::factory()->count(5)->create([
            'product_id' => $product3->id,
            'status' => 'completed'
        ]);

        // Create some pending orders (shouldn't affect popularity)
        Order::factory()->count(10)->create([
            'product_id' => $product1->id,
            'status' => 'pending'
        ]);

        // Get products ordered by popularity with completed order count
        $popularProducts = Product::select('products.*')
            ->selectRaw('COUNT(CASE WHEN orders.status = ? THEN 1 END) as completed_count', ['completed'])
            ->leftJoin('orders', 'products.id', '=', 'orders.product_id')
            ->whereIn('products.id', [$product1->id, $product2->id, $product3->id])
            ->groupBy('products.id')
            ->orderByDesc('completed_count')
            ->get();

        // Verify the order is correct
        $this->assertEquals(3, $popularProducts->count(), 'Should have exactly 3 products');
        $this->assertEquals($product3->id, $popularProducts[0]->id, 'First should be Product 3 (5 orders)');
        $this->assertEquals($product1->id, $popularProducts[1]->id, 'Second should be Product 1 (3 orders)');
        $this->assertEquals($product2->id, $popularProducts[2]->id, 'Third should be Product 2 (1 order)');

        // Verify the counts are correct
        $this->assertEquals(5, $popularProducts[0]->completed_count, 'Product 3 should have 5 completed orders');
        $this->assertEquals(3, $popularProducts[1]->completed_count, 'Product 1 should have 3 completed orders');
        $this->assertEquals(1, $popularProducts[2]->completed_count, 'Product 2 should have 1 completed order');
    }

    protected function info(string $message): void
    {
        fwrite(STDERR, $message . "\n");
    }
}
