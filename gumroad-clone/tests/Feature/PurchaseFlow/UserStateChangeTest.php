<?php

namespace Tests\Feature\PurchaseFlow;

use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserStateChangeTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_purchase_flow_with_user_deletion()
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

        // 4. Seller deletes their account
        $seller->delete();

        // 5. Verify buyer can still access their purchase
        $response = $this->actingAs($buyer)
            ->get(route('orders.show', $order));

        $response->assertStatus(200)
            ->assertSee($product->name)
            ->assertSee('Download');

        // 6. Verify product is still accessible
        $response = $this->actingAs($buyer)
            ->get(route('products.show', $product));

        $response->assertStatus(200);
    }
}
