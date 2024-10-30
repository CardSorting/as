<?php

namespace Tests\Unit\Product;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSlugTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_slug_generation()
    {
        $product = Product::factory()->create(['name' => 'Test Product Name']);

        $this->assertEquals('test-product-name', $product->slug);
        $this->assertMatchesRegularExpression('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $product->slug);
    }

    public function test_product_unique_slug_generation()
    {
        $product1 = Product::factory()->create(['name' => 'Test Product']);
        $product2 = Product::factory()->create(['name' => 'Test Product']);

        $this->assertNotEquals($product1->slug, $product2->slug);
        $this->assertStringStartsWith('test-product', $product1->slug);
        $this->assertStringStartsWith('test-product', $product2->slug);
        $this->assertMatchesRegularExpression('/^test-product-\d+$/', $product2->slug);
    }
}
