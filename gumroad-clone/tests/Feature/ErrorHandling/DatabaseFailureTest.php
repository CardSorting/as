<?php

namespace Tests\Feature\ErrorHandling;

use App\Models\StoreSilo;
use App\Services\StoreIsolation\DatabaseManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseFailureTest extends TestCase
{
    use RefreshDatabase;

    private DatabaseManager $dbManager;
    private StoreConnection $connection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dbManager = app(DatabaseManager::class);
        $this->connection = app(StoreConnection::class);
    }

    /** @test */
    public function corrupted_store_database_is_detected()
    {
        $store = StoreSilo::factory()->create();
        $this->dbManager->createStoreDatabase($store);
        
        // Corrupt the database file
        $dbPath = $this->dbManager->getDatabasePath($store);
        file_put_contents($dbPath, 'corrupted data');

        $response = $this->get("/store/{$store->store_domain}");
        
        $response->assertStatus(503);
        $this->assertDatabaseHas('store_silos', [
            'id' => $store->id,
            'status' => 'error'
        ]);
    }

    /** @test */
    public function system_remains_stable_when_store_database_is_missing()
    {
        $store = StoreSilo::factory()->create();
        $this->dbManager->createStoreDatabase($store);
        
        // Delete the database file
        unlink($this->dbManager->getDatabasePath($store));

        $response = $this->get("/store/{$store->store_domain}");
        
        $response->assertStatus(503);
        $this->assertDatabaseHas('store_silos', [
            'id' => $store->id,
            'status' => 'error'
        ]);
    }

    /** @test */
    public function other_stores_remain_accessible_during_database_failure()
    {
        $store1 = StoreSilo::factory()->create();
        $store2 = StoreSilo::factory()->create();
        
        $this->dbManager->createStoreDatabase($store1);
        $this->dbManager->createStoreDatabase($store2);
        
        // Corrupt store1's database
        $dbPath = $this->dbManager->getDatabasePath($store1);
        file_put_contents($dbPath, 'corrupted data');

        // Store1 should be inaccessible
        $response1 = $this->get("/store/{$store1->store_domain}");
        $response1->assertStatus(503);

        // Store2 should still work
        $response2 = $this->get("/store/{$store2->store_domain}");
        $response2->assertOk();
    }

    /** @test */
    public function admin_is_notified_of_database_failures()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $store = StoreSilo::factory()->create();
        
        $this->dbManager->createStoreDatabase($store);
        
        // Corrupt the database
        $dbPath = $this->dbManager->getDatabasePath($store);
        file_put_contents($dbPath, 'corrupted data');

        $response = $this->actingAs($admin)->get('/admin/dashboard/alerts');
        
        $response->assertOk()
            ->assertJsonStructure([
                'alerts' => [
                    '*' => [
                        'type',
                        'store_domain',
                        'message'
                    ]
                ]
            ])
            ->assertJsonFragment([
                'type' => 'database_error',
                'store_domain' => $store->store_domain
            ]);
    }
}
