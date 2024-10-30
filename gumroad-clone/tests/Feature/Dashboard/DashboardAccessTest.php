<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_empty_state_for_new_users()
    {
        $user = User::factory()->create();

        $salesResponse = $this->actingAs($user)
                             ->get(route('orders.sales'));
        $purchasesResponse = $this->actingAs($user)
                                 ->get(route('orders.purchases'));

        $salesResponse->assertStatus(200);
        $salesResponse->assertSee('No sales yet');
        $salesResponse->assertSee("You haven't made any sales yet");

        $purchasesResponse->assertStatus(200);
        $purchasesResponse->assertSee('No purchases yet');
        $purchasesResponse->assertSee("You haven't made any purchases yet");
    }
}
