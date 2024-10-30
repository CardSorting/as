<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class PurchaseFlowTest extends TestCase
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
