<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_guest_cannot_access_orders()
    {
        $response = $this->get(route('orders.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_user_can_view_their_orders()
    {
        $user = User::factory()->create();
        $orders = Order::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
                        ->get(route('orders.index'));

        $response->assertStatus(200);
        $response->assertViewHas('orders');
        foreach ($orders as $order) {
            $response->assertSee($order->product->name);
            $response->assertSee(number_format($order->amount, 2));
        }
    }

    public function test_user_can_purchase_product()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['is_published' => true]);

        $response = $this->actingAs($user)
                        ->post(route('orders.store', $product));

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'amount' => $product->price,
            'status' => 'completed'
        ]);
    }

    public function test_user_can_view_order_details()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed'
        ]);

        $response = $this->actingAs($user)
                        ->get(route('orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee($order->product->name);
        $response->assertSee(number_format($order->amount, 2));
        $response->assertSee('Completed');
    }

    public function test_user_cannot_view_others_orders()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)
                        ->get(route('orders.show', $order));

        $response->assertStatus(403);
    }

    public function test_seller_can_view_their_product_orders()
    {
        $seller = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $seller->id]);
        $buyer = User::factory()->create();
        $order = Order::factory()->create([
            'product_id' => $product->id,
            'user_id' => $buyer->id
        ]);

        $response = $this->actingAs($seller)
                        ->get(route('orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee($order->product->name);
        $response->assertSee($buyer->name);
    }

    public function test_user_can_view_purchases()
    {
        $user = User::factory()->create();
        $orders = Order::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
                        ->get(route('orders.purchases'));

        $response->assertStatus(200);
        foreach ($orders as $order) {
            $response->assertSee($order->product->name);
            $response->assertSee(number_format($order->amount, 2));
        }
    }

    public function test_user_can_view_sales()
    {
        $seller = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $seller->id]);
        $orders = Order::factory()->count(3)->create(['product_id' => $product->id]);

        $response = $this->actingAs($seller)
                        ->get(route('orders.sales'));

        $response->assertStatus(200);
        foreach ($orders as $order) {
            $response->assertSee($order->product->name);
            $response->assertSee(number_format($order->amount, 2));
        }
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

    public function test_cannot_purchase_unpublished_product()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['is_published' => false]);

        $response = $this->actingAs($user)
                        ->post(route('orders.store', $product));

        $response->assertStatus(404);
        $this->assertDatabaseMissing('orders', [
            'user_id' => $user->id,
            'product_id' => $product->id
        ]);
    }

    public function test_cannot_purchase_own_product()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'user_id' => $user->id,
            'is_published' => true
        ]);

        $response = $this->actingAs($user)
                        ->post(route('orders.store', $product));

        $response->assertStatus(403);
        $this->assertDatabaseMissing('orders', [
            'user_id' => $user->id,
            'product_id' => $product->id
        ]);
    }

    public function test_order_amount_must_match_product_price()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'price' => 99.99,
            'is_published' => true
        ]);

        $response = $this->actingAs($user)
                        ->post(route('orders.store', $product), [
                            'amount' => 50.00
                        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('orders', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'amount' => 50.00
        ]);
    }

    public function test_sales_pagination()
    {
        $seller = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $seller->id]);
        $orders = Order::factory()->count(25)->create(['product_id' => $product->id]);

        $response = $this->actingAs($seller)
                        ->get(route('orders.sales'));

        $response->assertStatus(200);
        $response->assertViewHas('orders');
        $this->assertEquals(20, $response->viewData('orders')->count()); // Assuming 20 per page
        $response->assertSee('Next');
    }

    public function test_purchases_pagination()
    {
        $user = User::factory()->create();
        $orders = Order::factory()->count(25)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
                        ->get(route('orders.purchases'));

        $response->assertStatus(200);
        $response->assertViewHas('orders');
        $this->assertEquals(20, $response->viewData('orders')->count()); // Assuming 20 per page
        $response->assertSee('Next');
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

    public function test_order_status_transition()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['is_published' => true]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($user)
                        ->patch(route('orders.update', $order), [
                            'status' => 'completed'
                        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'completed'
        ]);
    }

    public function test_invalid_order_status_transition()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['is_published' => true]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'status' => 'completed'
        ]);

        $response = $this->actingAs($user)
                        ->patch(route('orders.update', $order), [
                            'status' => 'invalid_status'
                        ]);

        $response->assertStatus(422);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'completed'
        ]);
    }
}
