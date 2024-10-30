<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\User;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_product_belongs_to_user()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $product->user);
        $this->assertEquals($user->id, $product->user->id);
    }

    public function test_product_has_price_as_float()
    {
        $product = Product::factory()->create(['price' => 99.99]);

        $this->assertIsFloat($product->price);
        $this->assertEquals(99.99, $product->price);
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

    public function test_product_file_path_generation()
    {
        $product = Product::factory()->create();
        $file = UploadedFile::fake()->create('test.pdf');

        $path = $product->generateFilePath($file);

        $this->assertStringStartsWith('products/' . $product->id . '/', $path);
        $this->assertStringEndsWith('.pdf', $path);
    }

    public function test_product_cover_image_path_generation()
    {
        $product = Product::factory()->create();
        $image = UploadedFile::fake()->image('cover.jpg');

        $path = $product->generateCoverImagePath($image);

        $this->assertStringStartsWith('products/' . $product->id . '/cover/', $path);
        $this->assertStringEndsWith('.jpg', $path);
    }

    public function test_product_price_formatting()
    {
        $product = Product::factory()->create(['price' => 99.99]);

        $this->assertEquals('$99.99', $product->formatted_price);
        $this->assertEquals(9999, $product->price_in_cents);
    }

    public function test_product_price_validation()
    {
        $product = Product::factory()->create();

        $this->expectException(\InvalidArgumentException::class);
        $product->price = -10;
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

    public function test_product_scope_by_price_range()
    {
        Product::factory()->create(['price' => 50]);
        Product::factory()->create(['price' => 100]);
        Product::factory()->create(['price' => 150]);

        $products = Product::priceRange(75, 125)->get();

        $this->assertEquals(1, $products->count());
        $this->assertEquals(100, $products->first()->price);
    }

    public function test_product_scope_by_popularity()
    {
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        $product3 = Product::factory()->create();

        // Create different numbers of orders for each product
        Order::factory()->count(3)->create(['product_id' => $product1->id]);
        Order::factory()->count(1)->create(['product_id' => $product2->id]);
        Order::factory()->count(5)->create(['product_id' => $product3->id]);

        $popularProducts = Product::orderByPopularity()->get();

        $this->assertEquals($product3->id, $popularProducts->first()->id);
        $this->assertEquals($product2->id, $popularProducts->last()->id);
    }

    public function test_product_cascading_delete()
    {
        $product = Product::factory()->create();
        
        // Create associated orders
        Order::factory()->count(3)->create(['product_id' => $product->id]);
        
        // Create files
        $file = UploadedFile::fake()->create('test.pdf');
        $cover = UploadedFile::fake()->image('cover.jpg');
        
        Storage::disk('public')->putFileAs(
            'products/' . $product->id,
            $file,
            'test.pdf'
        );
        
        Storage::disk('public')->putFileAs(
            'products/' . $product->id . '/cover',
            $cover,
            'cover.jpg'
        );

        // Delete product
        $product->delete();

        // Check if orders are deleted
        $this->assertEquals(0, Order::where('product_id', $product->id)->count());
        
        // Check if files are deleted
        Storage::disk('public')->assertMissing('products/' . $product->id);
    }

    public function test_product_slug_generation()
    {
        $product = Product::factory()->create(['name' => 'Test Product Name']);

        $this->assertEquals('test-product-name', $product->slug);
        $this->assertTrue(Str::isSlug($product->slug));
    }

    public function test_product_unique_slug_generation()
    {
        $product1 = Product::factory()->create(['name' => 'Test Product']);
        $product2 = Product::factory()->create(['name' => 'Test Product']);

        $this->assertNotEquals($product1->slug, $product2->slug);
    }

    public function test_product_file_validation()
    {
        $product = Product::factory()->create();

        $validFile = UploadedFile::fake()->create('document.pdf', 100);
        $oversizedFile = UploadedFile::fake()->create('large.pdf', 51200); // 50MB
        $invalidTypeFile = UploadedFile::fake()->create('script.exe', 100);

        $this->assertTrue($product->isValidFile($validFile));
        $this->assertFalse($product->isValidFile($oversizedFile));
        $this->assertFalse($product->isValidFile($invalidTypeFile));
    }

    public function test_product_cover_image_validation()
    {
        $product = Product::factory()->create();

        $validImage = UploadedFile::fake()->image('cover.jpg');
        $oversizedImage = UploadedFile::fake()->image('large.jpg')->size(5120); // 5MB
        $invalidTypeFile = UploadedFile::fake()->create('document.pdf');

        $this->assertTrue($product->isValidCoverImage($validImage));
        $this->assertFalse($product->isValidCoverImage($oversizedImage));
        $this->assertFalse($product->isValidCoverImage($invalidTypeFile));
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

    public function test_product_search_scope()
    {
        Product::factory()->create(['name' => 'PHP Course']);
        Product::factory()->create(['name' => 'JavaScript Guide']);
        Product::factory()->create(['name' => 'Python Tutorial']);

        $results = Product::search('php')->get();
        $this->assertEquals(1, $results->count());
        $this->assertEquals('PHP Course', $results->first()->name);
    }

    public function test_product_recent_orders_relation()
    {
        $product = Product::factory()->create();
        
        // Create orders with different dates
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
