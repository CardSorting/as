<?php

namespace Tests\Feature\Admin\Monitoring;

use App\Models\StoreSilo;
use App\Models\User;
use App\Models\SiloTransaction;
use App\Models\SiloBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    /** @test */
    public function admin_can_see_overview_of_all_stores()
    {
        $stores = StoreSilo::factory(3)
            ->has(SiloBalance::factory())
            ->create();

        $response = $this->actingAs($this->admin)
            ->get('/admin/dashboard');

        $response->assertOk();
        
        $stores->each(function ($store) use ($response) {
            $response->assertSee([
                $store->store_domain,
                $store->balance->current_balance
            ]);
        });
    }

    /** @test */
    public function admin_can_see_stores_requiring_attention()
    {
        // Store with high unpaid balance
        $highBalanceStore = StoreSilo::factory()
            ->has(SiloBalance::factory(['current_balance' => 1000]))
            ->create();

        // Store with recent failed transactions
        $problemStore = StoreSilo::factory()->create();
        SiloTransaction::factory()->create([
            'store_silo_id' => $problemStore->id,
            'status' => 'failed'
        ]);

        $response = $this->actingAs($this->admin)
            ->get('/admin/dashboard/attention-required');

        $response->assertOk()
            ->assertSee($highBalanceStore->store_domain)
            ->assertSee($problemStore->store_domain);
    }

    /** @test */
    public function admin_can_filter_stores_by_status()
    {
        $activeStore = StoreSilo::factory()->create(['status' => 'active']);
        $suspendedStore = StoreSilo::factory()->create(['status' => 'suspended']);

        $response = $this->actingAs($this->admin)
            ->get('/admin/dashboard?status=suspended');

        $response->assertOk()
            ->assertSee($suspendedStore->store_domain)
            ->assertDontSee($activeStore->store_domain);
    }

    /** @test */
    public function admin_can_see_system_health_metrics()
    {
        StoreSilo::factory(5)->create();
        SiloTransaction::factory(10)->create();

        $response = $this->actingAs($this->admin)
            ->get('/admin/dashboard/health');

        $response->assertOk()
            ->assertJsonStructure([
                'total_stores',
                'active_stores',
                'total_transactions_24h',
                'failed_transactions_24h',
                'average_transaction_time',
                'system_storage_usage'
            ]);
    }

    /** @test */
    public function admin_receives_real_time_alerts()
    {
        $store = StoreSilo::factory()->create();
        
        // Simulate multiple failed transactions
        SiloTransaction::factory(5)->create([
            'store_silo_id' => $store->id,
            'status' => 'failed',
            'created_at' => now()
        ]);

        $response = $this->actingAs($this->admin)
            ->get('/admin/dashboard/alerts');

        $response->assertOk()
            ->assertJsonStructure([
                'alerts' => [
                    '*' => [
                        'type',
                        'store_domain',
                        'message',
                        'created_at'
                    ]
                ]
            ]);
    }
}
