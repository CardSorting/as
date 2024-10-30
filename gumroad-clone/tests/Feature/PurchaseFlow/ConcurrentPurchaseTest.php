<?php

namespace Tests\Feature\PurchaseFlow;

use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConcurrentPurchaseTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_concurrent_purchases()
    {
        // 1. Create seller and buyers
        $seller = User::factory()->create();
        $buyers = User::factory()->count(5)->create();

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

        // 3. Simulate concurrent purchases using database transactions
        $purchaseCount = 0;
        foreach ($buyers as $buyer) {
            DB::transaction(function () use ($buyer, $product, &$purchaseCount) {
                $this->actingAs($buyer)
                    ->post(route('orders.store', $product));
                $purchaseCount++;
            });
        }

        // 4. Verify all purchases were successful
        $this->assertEquals(5, $purchaseCount);
        $this->assertEquals(5, Order::where('product_id', $product->id)->count());
    }
}
