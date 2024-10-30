<?php

namespace Tests\Feature\Admin\SiloMonitoring;

use App\Models\SiloBalance;
use App\Models\StoreSilo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BalanceTrackingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    /** @test */
    public function admin_can_view_all_silo_balances()
    {
        $silos = StoreSilo::factory(3)
            ->has(SiloBalance::factory())
            ->create();

        $response = $this->actingAs($this->admin)
            ->get('/admin/silos');

        $response->assertOk();
        $silos->each(function ($silo) use ($response) {
            $response->assertSee([
                $silo->store_domain,
                number_format($silo->balance->current_balance, 2)
            ]);
        });
    }

    /** @test */
    public function admin_can_filter_silos_by_balance_threshold()
    {
        // Create silos with different balances
        $highBalanceSilo = StoreSilo::factory()
            ->has(SiloBalance::factory(['current_balance' => 1000]))
            ->create();

        $lowBalanceSilo = StoreSilo::factory()
            ->has(SiloBalance::factory(['current_balance' => 10]))
            ->create();

        $response = $this->actingAs($this->admin)
            ->get('/admin/silos?min_balance=100');

        $response->assertOk()
            ->assertSee($highBalanceSilo->store_domain)
            ->assertDontSee($lowBalanceSilo->store_domain);
    }

    /** @test */
    public function admin_can_view_detailed_balance_history()
    {
        $silo = StoreSilo::factory()
            ->has(SiloBalance::factory())
            ->create();

        $response = $this->actingAs($this->admin)
            ->get("/admin/silos/{$silo->id}/balance-history");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'date',
                        'balance',
                        'transactions_count'
                    ]
                ]
            ]);
    }
}
