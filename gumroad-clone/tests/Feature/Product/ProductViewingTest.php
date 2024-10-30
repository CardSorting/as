<?php

namespace Tests\Feature\Product;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductViewingTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_displays_published_products()
    {
        $publishedProducts = Product::factory()->count(3)->create(['is_published' => true]);
        Product::factory()->count(2)->create(['is_published' => false]);

        $response = $this->get(route('products.index'));

        $response->assertStatus(200);
        $response->assertViewHas('products');
        foreach ($publishedProducts as $product) {
            $response->assertSee($product->name);
        }
    }

    public function test_show_displays_product_details()
    {
        $product = Product::factory()->create(['is_published' => true]);

        $response = $this->get(route('products.show', $product));

        $response->assertStatus(200);
        $response->assertSee($product->name);
        $response->assertSee($product->description);
        $response->assertSee(number_format($product->price, 2));
    }

    public function test_unpublished_product_is_not_visible_to_guests()
    {
        $product = Product::factory()->create(['is_published' => false]);

        $response = $this->get(route('products.show', $product));

        $response->assertStatus(404);
    }
}
