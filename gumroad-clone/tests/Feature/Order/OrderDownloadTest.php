<?php

namespace Tests\Feature\Order;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_completed_order_shows_download_link()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'file_path' => 'products/test.pdf',
            'is_published' => true
        ]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'status' => 'completed'
        ]);

        $response = $this->actingAs($user)
                        ->get(route('orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee('Download');
    }

    public function test_pending_order_hides_download_link()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'file_path' => 'products/test.pdf',
            'is_published' => true
        ]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($user)
                        ->get(route('orders.show', $order));

        $response->assertStatus(200);
        $response->assertDontSee('Download');
        $response->assertSee('Pending');
    }

    public function test_order_download_requires_completed_status()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'file_path' => 'products/test.pdf',
            'is_published' => true
        ]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($user)
                        ->get(route('orders.download', $order));

        $response->assertStatus(403);
    }

    public function test_order_download_requires_ownership()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $product = Product::factory()->create([
            'file_path' => 'products/test.pdf',
            'is_published' => true
        ]);
        $order = Order::factory()->create([
            'user_id' => $otherUser->id,
            'product_id' => $product->id,
            'status' => 'completed'
        ]);

        $response = $this->actingAs($user)
                        ->get(route('orders.download', $order));

        $response->assertStatus(403);
    }

    public function test_successful_order_download()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('test.pdf', 100);
        Storage::disk('public')->putFileAs('products', $file, 'test.pdf');

        $product = Product::factory()->create([
            'file_path' => 'products/test.pdf',
            'is_published' => true
        ]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'status' => 'completed'
        ]);

        $response = $this->actingAs($user)
                        ->get(route('orders.download', $order));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }
}
