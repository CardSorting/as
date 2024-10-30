<?php

namespace Tests\Feature\PurchaseFlow;

use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileUpdateTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_purchase_flow_with_file_update()
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

        // 4. Seller updates the product file
        $newFile = UploadedFile::fake()->create('updated-file.pdf', 100);
        
        $this->actingAs($seller)
            ->put(route('products.update', $product), [
                'name' => $product->name,
                'description' => $product->description,
                'price' => $product->price,
                'file_path' => $newFile,
                'is_published' => true
            ]);

        // 5. Verify buyer can download the updated file
        $response = $this->actingAs($buyer)
            ->get(route('orders.download', $order));

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');
    }
}
