<?php

namespace Tests\Unit\Product;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use InvalidArgumentException;

class ProductPriceTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_has_price_as_float()
    {
        $product = Product::factory()->create(['price' => 99.99]);

        $this->assertIsFloat($product->price);
        $this->assertEquals(99.99, $product->price);
    }

    public function test_product_price_formatting()
    {
        $product = Product::factory()->create(['price' => 99.99]);

        $this->assertEquals('$99.99', $product->formatted_price);
        $this->assertEquals(9999, $product->price_in_cents);
    }

    public function test_product_price_validation()
    {
        $product = Product::factory()->create(['price' => 99.99]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Product price cannot be negative.');
        
        $product->price = -10;
        $product->save();
    }

    public function test_product_scope_by_price_range()
    {
        Product::factory()->create(['price' => 50]);
        Product::factory()->create(['price' => 100]);
        Product::factory()->create(['price' => 150]);

        $products = Product::priceRange(75, 125)->get();

        $this->assertEquals(1, $products->count());
        $this->assertEquals(100, $products->first()->price);
    }
}
