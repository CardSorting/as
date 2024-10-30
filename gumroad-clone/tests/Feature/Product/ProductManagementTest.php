<?php

namespace Tests\Feature\Product;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_edit_own_product()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $user->id]);
        
        $updatedData = [
            'name' => 'Updated Name',
            'description' => 'Updated Description',
            'price' => 149.99,
            'is_published' => true
        ];

        $response = $this->actingAs($user)
                        ->put(route('products.update', $product), $updatedData);

        $response->assertRedirect(route('products.show', $product));
        $this->assertDatabaseHas('products', $updatedData + ['id' => $product->id]);
    }

    public function test_user_cannot_edit_others_product()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)
                        ->get(route('products.edit', $product));

        $response->assertStatus(403);
    }

    public function test_user_can_delete_own_product()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
                        ->delete(route('products.destroy', $product));

        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_user_cannot_delete_others_product()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)
                        ->delete(route('products.destroy', $product));

        $response->assertStatus(403);
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_user_can_publish_own_product()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'user_id' => $user->id,
            'is_published' => false
        ]);

        $response = $this->actingAs($user)
                        ->put(route('products.update', $product), [
                            'name' => $product->name,
                            'description' => $product->description,
                            'price' => $product->price,
                            'is_published' => true
                        ]);

        $response->assertRedirect(route('products.show', $product));
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'is_published' => true
        ]);
    }
}
