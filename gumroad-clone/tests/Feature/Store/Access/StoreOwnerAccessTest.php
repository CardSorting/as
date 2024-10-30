<?php

namespace Tests\Feature\Store\Access;

use App\Models\StoreSilo;
use App\Models\User;
use App\Models\Store\Product;
use App\Services\StoreIsolation\DatabaseManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreOwnerAccessTest extends TestCase
{
    use RefreshDatabase;

    private DatabaseManager $dbManager;
    private StoreConnection $connection;
    private StoreSilo $store;
    private User $owner;
    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dbManager = app(DatabaseManager::class);
        $this->connection = app(StoreConnection::class);
        
        $this->owner = User::factory()->create();
        $this->otherUser = User::factory()->create();
        
        $this->store = StoreSilo::factory()->create([
            'user_id' => $this->owner->id
        ]);
        
        $this->dbManager->createStoreDatabase($this->store);
        $this->connection->connect($this->store);
    }

    /** @test */
    public function owner_can_access_store_dashboard()
    {
        $response = $this->actingAs($this->owner)
            ->get("/store/{$this->store->store_domain}/dashboard");

        $response->assertOk();
    }

    /** @test */
    public function non_owner_cannot_access_store_dashboard()
    {
        $response = $this->actingAs($this->otherUser)
            ->get("/store/{$this->store->store_domain}/dashboard");

        $response->assertForbidden();
    }

    /** @test */
    public function owner_can_manage_products()
    {
        $response = $this->actingAs($this->owner)
            ->post("/store/{$this->store->store_domain}/products", [
                'name' => 'Test Product',
                'price' => 10.00
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('products', [
            'name' => 'Test Product'
        ], $this->dbManager->getConnectionName($this->store));
    }

    /** @test */
    public function non_owner_cannot_manage_products()
    {
        $response = $this->actingAs($this->otherUser)
            ->post("/store/{$this->store->store_domain}/products", [
                'name' => 'Test Product',
                'price' => 10.00
            ]);

        $response->assertForbidden();
    }
}
