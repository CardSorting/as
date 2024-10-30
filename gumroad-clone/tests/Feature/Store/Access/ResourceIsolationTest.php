<?php

namespace Tests\Feature\Store\Access;

use App\Models\StoreSilo;
use App\Models\User;
use App\Models\Store\Product;
use App\Models\Store\Order;
use App\Services\StoreIsolation\DatabaseManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceIsolationTest extends TestCase
{
    use RefreshDatabase;

    private DatabaseManager $dbManager;
    private StoreConnection $connection;
    private StoreSilo $store1;
    private StoreSilo $store2;
    private User $owner1;
    private User $owner2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dbManager = app(DatabaseManager::class);
        $this->connection = app(StoreConnection::class);
        
        $this->owner1 = User::factory()->create();
        $this->owner2 = User::factory()->create();
        
        $this->store1 = StoreSilo::factory()->create(['user_id' => $this->owner1->id]);
        $this->store2 = StoreSilo::factory()->create(['user_id' => $this->owner2->id]);
        
        $this->dbManager->createStoreDatabase($this->store1);
        $this->dbManager->createStoreDatabase($this->store2);
    }

    /** @test */
    public function owner_cannot_access_other_store_products()
    {
        // Create product in store 1
        $this->connection->connect($this->store1);
        $product = Product::create([
            'name' => 'Store 1 Product',
            'price' => 10.00
        ]);

        // Try to access from store 2
        $response = $this->actingAs($this->owner2)
            ->get("/store/{$this->store1->store_domain}/products/{$product->id}/edit");

        $response->assertForbidden();
    }

    /** @test */
    public function owner_cannot_access_other_store_orders()
    {
        // Create order in store 1
        $this->connection->connect($this->store1);
        $product = Product::create([
            'name' => 'Store 1 Product',
            'price' => 10.00
        ]);
        $order = Order::create([
            'product_id' => $product->id,
            'order_number' => 'ORD-' . uniqid(),
            'amount' => 10.00,
            'status' => 'paid',
            'customer_details' => ['email' => 'customer@example.com']
        ]);

        // Try to access from store 2
        $response = $this->actingAs($this->owner2)
            ->get("/store/{$this->store1->store_domain}/orders/{$order->id}");

        $response->assertForbidden();
    }

    /** @test */
    public function owner_cannot_modify_other_store_settings()
    {
        $response = $this->actingAs($this->owner2)
            ->put("/store/{$this->store1->store_domain}/settings", [
                'store_name' => 'Hacked Store'
            ]);

        $response->assertForbidden();
    }

    /** @test */
    public function owner_cannot_access_other_store_analytics()
    {
        $response = $this->actingAs($this->owner2)
            ->get("/store/{$this->store1->store_domain}/analytics");

        $response->assertForbidden();
    }
}
