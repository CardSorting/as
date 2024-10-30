<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_user_can_register()
    {
        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post(route('register'), $userData);

        $response->assertRedirect('/');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => $userData['email'],
            'name' => $userData['name'],
        ]);
    }

    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticated();
    }

    public function test_user_cannot_login_with_invalid_password()
    {
        $user = User::factory()->create();

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_user_can_logout()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
                        ->post(route('logout'));

        $response->assertRedirect('/');
        $this->assertGuest();
    }

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

    public function test_registration_requires_valid_email()
    {
        $response = $this->post(route('register'), [
            'name' => $this->faker->name,
            'email' => 'not-an-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_registration_requires_password_confirmation()
    {
        $response = $this->post(route('register'), [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password123',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_user_cannot_register_with_existing_email()
    {
        $existingUser = User::factory()->create();

        $response = $this->post(route('register'), [
            'name' => $this->faker->name,
            'email' => $existingUser->email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_authenticated_user_cannot_access_login_or_register_pages()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('login'));
        $response->assertRedirect('/');

        $response = $this->actingAs($user)->get(route('register'));
        $response->assertRedirect('/');
    }

    public function test_user_can_verify_email()
    {
        $user = User::factory()->create([
            'email_verified_at' => null
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        $response->assertRedirect('/');
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }
}
