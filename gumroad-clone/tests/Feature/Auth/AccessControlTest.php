<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_protected_routes()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $user->id]);
        
        $protectedRoutes = [
            route('products.create'),
            route('orders.index'),
            route('orders.purchases'),
            route('orders.sales'),
            route('profile.edit'),
            route('products.edit', $product),
        ];

        foreach ($protectedRoutes as $route) {
            $response = $this->get($route);
            $response->assertRedirect(route('login'));
        }
    }
}
