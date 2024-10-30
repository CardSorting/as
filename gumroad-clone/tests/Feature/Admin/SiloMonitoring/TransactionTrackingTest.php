<?php

namespace Tests\Feature\Admin\SiloMonitoring;

use App\Models\StoreSilo;
use App\Models\SiloTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTrackingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private StoreSilo $silo;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->silo = StoreSilo::factory()->create();
    }

    /** @test */
    public function admin_can_view_silo_transactions()
    {
        $transactions = SiloTransaction::factory(3)->create([
            'store_silo_id' => $this->silo->id
        ]);

        $response = $this->actingAs($this->admin)
            ->get("/admin/silos/{$this->silo->id}/transactions");

        $response->assertOk()
            ->assertJsonCount(3, 'transactions.data')
            ->assertJsonStructure([
                'transactions' => [
                    'data' => [
                        '*' => [
                            'id',
                            'transaction_id',
                            'amount',
                            'transaction_date',
                            'is_paid',
                            'paid_at'
                        ]
                    ]
                ]
            ]);
    }

    /** @test */
    public function admin_cannot_modify_silo_transactions()
    {
        $transaction = SiloTransaction::factory()->create([
            'store_silo_id' => $this->silo->id,
            'is_paid' => false
        ]);

        $response = $this->actingAs($this->admin)
            ->patch("/admin/silos/{$this->silo->id}/transactions/{$transaction->id}", [
                'is_paid' => true
            ]);

        $response->assertForbidden();
        $this->assertFalse($transaction->fresh()->is_paid);
    }

    /** @test */
    public function admin_can_export_silo_transactions()
    {
        SiloTransaction::factory(5)->create([
            'store_silo_id' => $this->silo->id
        ]);

        $response = $this->actingAs($this->admin)
            ->get("/admin/silos/{$this->silo->id}/export");

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertHeader('Content-Disposition', 'attachment; filename=transactions.csv');
    }
}
