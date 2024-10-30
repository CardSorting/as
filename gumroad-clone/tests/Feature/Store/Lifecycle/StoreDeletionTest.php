<?php

namespace Tests\Feature\Store\Lifecycle;

use App\Models\StoreSilo;
use App\Models\User;
use App\Services\StoreIsolation\DatabaseManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreDeletionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private StoreSilo $store;
    private DatabaseManager $dbManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->store = StoreSilo::factory()->create(['user_id' => $this->user->id]);
        $this->dbManager = app(DatabaseManager::class);
        
        // Set up store database
        $this->dbManager->createStoreDatabase($this->store);
    }

    /** @test */
    public function owner_can_delete_store()
    {
        $response = $this->actingAs($this->user)
            ->delete("/stores/{$this->store->id}", [
                'confirmation' => $this->store->store_domain
            ]);

        $response->assertRedirect();
        
        $this->assertDatabaseMissing('store_silos', [
            'id' => $this->store->id
        ]);
    }

    /** @test */
    public function store_deletion_removes_database_file()
    {
        $dbPath = $this->dbManager->getDatabasePath($this->store);

        $this->actingAs($this->user)
            ->delete("/stores/{$this->store->id}", [
                'confirmation' => $this->store->store_domain
            ]);

        $this->assertFalse(
            file_exists($dbPath),
            'Store database file was not removed'
        );
    }

    /** @test */
    public function store_deletion_requires_confirmation()
    {
        $response = $this->actingAs($this->user)
            ->delete("/stores/{$this->store->id}", [
                'confirmation' => 'wrong-confirmation'
            ]);

        $response->assertSessionHasErrors('confirmation');
        
        $this->assertDatabaseHas('store_silos', [
            'id' => $this->store->id
        ]);
    }

    /** @test */
    public function non_owner_cannot_delete_store()
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->delete("/stores/{$this->store->id}", [
                'confirmation' => $this->store->store_domain
            ]);

        $response->assertForbidden();
        
        $this->assertDatabaseHas('store_silos', [
            'id' => $this->store->id
        ]);
    }

    /** @test */
    public function deletion_cleans_up_all_store_resources()
    {
        // Create some store files
        $storageDir = storage_path("store-files/{$this->store->id}");
        mkdir($storageDir, 0755, true);
        file_put_contents($storageDir . '/test.txt', 'test');

        $this->actingAs($this->user)
            ->delete("/stores/{$this->store->id}", [
                'confirmation' => $this->store->store_domain
            ]);

        $this->assertFalse(
            file_exists($storageDir),
            'Store files directory was not removed'
        );
    }
}
