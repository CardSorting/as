<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RegistrationTest extends TestCase
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
}
