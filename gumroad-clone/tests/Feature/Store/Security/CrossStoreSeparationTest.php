<?php

namespace Tests\Feature\Store\Security;

use App\Models\Store\Product;
use App\Models\StoreSilo;
use App\Models\User;
use App\Services\StoreIsolation\DatabaseManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrossStoreSeparationTest extends TestCase
{
    use RefreshDatabase;

    private User $user1;
    private User $user2;
    private StoreSilo $store1;
    private StoreSilo $store2;
    private DatabaseManager $dbManager;
    private StoreConnection $connection;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user1 = User::factory()->create();
        $this->user2 = User::factory()->create();
        
        $this->store1 = StoreSilo::factory()->create(['user_id' => $this->user1->id]);
        $this->store2 = StoreSilo::factory()->create(['user_id' => $this->user2->id]);
        
        $this->dbManager = app(DatabaseManager::class);
        $this->connection = app(StoreConnection::class);
        
        // Set up store databases
        $this->dbManager->createStoreDatabase($this->store1);
        $this->dbManager->createStoreDatabase($this->store2);
    }

    /** @test */
    public function stores_have_separate_product_namespaces()
    {
        // Create product in store 1
        $this->connection->connect($this->store1);
        $product1 = Product::create([
            'name' => 'Test Product',
            'price' => 10.00
        ]);

        // Create product with same name in store 2
        $this->connection->connect($this->store2);
        $product2 = Product::create([
            'name' => 'Test Product',
            'price' => 20.00
        ]);

        $this->assertNotEquals($product1->id, $product2->id);
    }

    /** @test */
    public function store_owner_cannot_access_other_stores()
    {
        $response = $this->actingAs($this->user1)
            ->get("/admin/stores/{$this->store2->id}/dashboard");

        $response->assertForbidden();
    }

    /** @test */
    public function store_api_requests_are_isolated()
    {
        // Create product in store 1
        $this->connection->connect($this->store1);
        $product = Product::create([
            'name' => 'Store 1 Product',
            'price' => 10.00
        ]);

        // Try to access product through store 2's API
        $response = $this->get("/store/{$this->store2->store_domain}/api/products/{$product->id}");

        $response->assertNotFound();
    }

    /** @test */
    public function file_storage_is_isolated()
    {
        $store1Path = storage_path("store-files/{$this->store1->id}/test.txt");
        $store2Path = storage_path("store-files/{$this->store2->id}/test.txt");

        // Create test file for store 1
        mkdir(dirname($store1Path), 0755, true);
        file_put_contents($store1Path, 'store 1 data');

        // Create test file for store 2
        mkdir(dirname($store2Path), 0755, true);
        file_put_contents($store2Path, 'store 2 data');

        // Verify store 1 can't access store 2's files
        $response = $this->actingAs($this->user1)
            ->get("/store/{$this->store1->store_domain}/files/test.txt");

        $this->assertNotEquals(
            file_get_contents($store2Path),
            $response->getContent()
        );
    }
}
