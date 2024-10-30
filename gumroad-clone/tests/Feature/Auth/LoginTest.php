<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_authenticated_user_cannot_access_login_or_register_pages()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('login'));
        $response->assertRedirect('/');

        $response = $this->actingAs($user)->get(route('register'));
        $response->assertRedirect('/');
    }
}
