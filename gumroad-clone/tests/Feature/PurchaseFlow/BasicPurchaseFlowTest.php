<?php

namespace Tests\Feature\PurchaseFlow;

use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BasicPurchaseFlowTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_complete_purchase_flow()
    {
        // 1. Create seller and buyer
        $seller = User::factory()->create();
        $buyer = User::factory()->create();

        // 2. Seller creates a product
        $file = UploadedFile::fake()->create('test-file.pdf', 100);
        $cover = UploadedFile::fake()->image('test-cover.jpg');

        $productData = [
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 29.99,
            'cover_image' => $cover,
            'file_path' => $file,
            'is_published' => true
        ];

        $response = $this->actingAs($seller)
            ->post(route('products.store'), $productData);

        $product = $seller->products()->first();
        $this->assertNotNull($product);

        // 3. Buyer views the product
        $response = $this->actingAs($buyer)
            ->get(route('products.show', $product));
        
        $response->assertStatus(200)
            ->assertSee($product->name)
            ->assertSee(number_format($product->price, 2));

        // 4. Buyer purchases the product
        $response = $this->actingAs($buyer)
            ->post(route('orders.store', $product));

        $response->assertRedirect();
        
        // 5. Verify order creation
        $order = $buyer->orders()->first();
        $this->assertNotNull($order);
        $this->assertEquals($product->id, $order->product_id);
        $this->assertEquals($product->price, $order->amount);
        $this->assertEquals('completed', $order->status);

        // 6. Buyer views order details
        $response = $this->actingAs($buyer)
            ->get(route('orders.show', $order));

        $response->assertStatus(200)
            ->assertSee($product->name)
            ->assertSee(number_format($order->amount, 2))
            ->assertSee('Download');

        // 7. Seller views the sale
        $response = $this->actingAs($seller)
            ->get(route('orders.sales'));

        $response->assertStatus(200)
            ->assertSee($product->name)
            ->assertSee($buyer->name)
            ->assertSee(number_format($order->amount, 2));
    }

    public function test_purchase_flow_with_unpublished_product()
    {
        // 1. Create seller and buyer
        $seller = User::factory()->create();
        $buyer = User::factory()->create();

        // 2. Seller creates an unpublished product
        $file = UploadedFile::fake()->create('test-file.pdf', 100);
        $cover = UploadedFile::fake()->image('test-cover.jpg');

        $productData = [
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 29.99,
            'cover_image' => $cover,
            'file_path' => $file,
            'is_published' => false
        ];

        $this->actingAs($seller)
            ->post(route('products.store'), $productData);

        $product = $seller->products()->first();

        // 3. Buyer attempts to view the product
        $response = $this->actingAs($buyer)
            ->get(route('products.show', $product));

        $response->assertStatus(404);

        // 4. Buyer attempts to purchase the product
        $response = $this->actingAs($buyer)
            ->post(route('orders.store', $product));

        $response->assertStatus(404);

        // 5. Verify no order was created
        $this->assertEquals(0, $buyer->orders()->count());
    }

    public function test_purchase_flow_with_multiple_orders()
    {
        // 1. Create seller and buyers
        $seller = User::factory()->create();
        $buyers = User::factory()->count(3)->create();

        // 2. Seller creates a product
        $file = UploadedFile::fake()->create('test-file.pdf', 100);
        $cover = UploadedFile::fake()->image('test-cover.jpg');

        $productData = [
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 29.99,
            'cover_image' => $cover,
            'file_path' => $file,
            'is_published' => true
        ];

        $this->actingAs($seller)
            ->post(route('products.store'), $productData);

        $product = $seller->products()->first();

        // 3. Multiple buyers purchase the product
        foreach ($buyers as $buyer) {
            $this->actingAs($buyer)
                ->post(route('orders.store', $product));
        }

        // 4. Verify orders for each buyer
        foreach ($buyers as $buyer) {
            $order = $buyer->orders()->first();
            $this->assertNotNull($order);
            $this->assertEquals($product->id, $order->product_id);
        }

        // 5. Verify seller's sales count
        $response = $this->actingAs($seller)
            ->get(route('orders.sales'));

        $response->assertStatus(200);
        $this->assertEquals(3, $seller->products()->first()->orders()->count());
    }
}
