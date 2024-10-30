<?php

namespace Tests\Feature\Product;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_rules_for_product_creation()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
                        ->post(route('products.store'), [
                            'name' => '',
                            'description' => '',
                            'price' => 'not-a-number'
                        ]);

        $response->assertSessionHasErrors(['name', 'description', 'price', 'file_path']);
    }

    public function test_validation_rules_for_product_update()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
                        ->put(route('products.update', $product), [
                            'name' => '',
                            'description' => '',
                            'price' => 'invalid-price'
                        ]);

        $response->assertSessionHasErrors(['name', 'description', 'price']);
    }
}
