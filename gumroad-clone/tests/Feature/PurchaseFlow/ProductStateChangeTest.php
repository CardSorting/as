<?php

namespace Tests\Feature\PurchaseFlow;

use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductStateChangeTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_purchase_flow_with_price_update()
    {
        // 1. Create seller and buyers
        $seller = User::factory()->create();
        $buyer1 = User::factory()->create();
        $buyer2 = User::factory()->create();

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

        // 3. First buyer purchases at original price
        $this->actingAs($buyer1)
            ->post(route('orders.store', $product));

        $order1 = $buyer1->orders()->first();
        $this->assertEquals(29.99, $order1->amount);

        // 4. Seller updates price
        $this->actingAs($seller)
            ->put(route('products.update', $product), [
                'name' => $product->name,
                'description' => $product->description,
                'price' => 39.99,
                'is_published' => true
            ]);

        // 5. Second buyer purchases at new price
        $this->actingAs($buyer2)
            ->post(route('orders.store', $product));

        $order2 = $buyer2->orders()->first();
        $this->assertEquals(39.99, $order2->amount);
    }

    public function test_purchase_flow_with_product_deletion()
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

        $this->actingAs($seller)
            ->post(route('products.store'), $productData);

        $product = $seller->products()->first();

        // 3. Buyer purchases the product
        $this->actingAs($buyer)
            ->post(route('orders.store', $product));

        $order = $buyer->orders()->first();
        $this->assertNotNull($order);

        // 4. Seller deletes the product
        $this->actingAs($seller)
            ->delete(route('products.destroy', $product));

        // 5. Verify buyer can still access their purchase
        $response = $this->actingAs($buyer)
            ->get(route('orders.show', $order));

        $response->assertStatus(200)
            ->assertSee($product->name)
            ->assertSee('Download');
    }
}
