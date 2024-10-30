<?php

namespace Tests\Unit\Product;

use App\Models\Product;
use App\Models\User;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductBasicTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_belongs_to_user()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $product->user);
        $this->assertEquals($user->id, $product->user->id);
    }

    public function test_product_can_be_published()
    {
        $product = Product::factory()->create(['is_published' => false]);
        
        $this->assertFalse($product->is_published);
        
        $product->is_published = true;
        $product->save();
        
        $this->assertTrue($product->fresh()->is_published);
    }

    public function test_product_has_orders()
    {
        $product = Product::factory()->create();
        
        $this->assertIsIterable($product->orders);
    }

    public function test_can_get_published_products()
    {
        Product::factory()->count(3)->create(['is_published' => true]);
        Product::factory()->count(2)->create(['is_published' => false]);

        $publishedProducts = Product::where('is_published', true)->get();

        $this->assertEquals(3, $publishedProducts->count());
    }

    public function test_product_url_generation()
    {
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'is_published' => true
        ]);

        $expectedUrl = url('/products/' . $product->slug);
        $this->assertEquals($expectedUrl, $product->url);
    }

    public function test_product_recent_orders_relation()
    {
        $product = Product::factory()->create();
        
        Order::factory()->create([
            'product_id' => $product->id,
            'created_at' => now()->subDays(1)
        ]);
        Order::factory()->create([
            'product_id' => $product->id,
            'created_at' => now()->subDays(5)
        ]);
        Order::factory()->create([
            'product_id' => $product->id,
            'created_at' => now()->subDays(10)
        ]);

        $this->assertEquals(2, $product->recent_orders->count());
        $this->assertTrue(
            $product->recent_orders->first()->created_at->gt(
                $product->recent_orders->last()->created_at
            )
        );
    }
}
