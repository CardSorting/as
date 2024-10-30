<?php

namespace Tests\Feature\Store\Delivery;

use App\Models\StoreSilo;
use App\Models\Store\Product;
use App\Models\Store\Order;
use App\Services\StoreIsolation\DatabaseManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CustomerAccessTest extends TestCase
{
    use RefreshDatabase;

    private DatabaseManager $dbManager;
    private StoreConnection $connection;
    private StoreSilo $store;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dbManager = app(DatabaseManager::class);
        $this->connection = app(StoreConnection::class);
        
        $this->store = StoreSilo::factory()->create();
        $this->dbManager->createStoreDatabase($this->store);
        $this->connection->connect($this->store);
        
        $file = UploadedFile::fake()->create('test.pdf', 100);
        
        $product = Product::create([
            'name' => 'Digital Product',
            'price' => 10.00,
            'is_digital' => true,
            'files' => [[
                'path' => $file->store("products/{$this->store->id}", 'store_files'),
                'name' => 'test.pdf',
                'size' => $file->getSize(),
                'type' => 'application/pdf'
            ]]
        ]);

        $this->order = Order::create([
            'product_id' => $product->id,
            'order_number' => 'ORD-' . uniqid(),
            'amount' => $product->price,
            'status' => 'paid',
            'paid_at' => now(),
            'customer_details' => ['email' => 'test@example.com']
        ]);
    }

    /** @test */
    public function it_validates_customer_email()
    {
        $response = $this->get("/store/{$this->store->store_domain}/orders/{$this->order->id}/download/0", [
            'email' => 'wrong@example.com'
        ]);

        $response->assertForbidden();
    }

    /** @test */
    public function it_allows_access_with_correct_email()
    {
        $response = $this->get("/store/{$this->store->store_domain}/orders/{$this->order->id}/download/0", [
            'email' => 'test@example.com'
        ]);

        $response->assertOk();
    }

    /** @test */
    public function it_prevents_cross_store_access()
    {
        // Create another store
        $store2 = StoreSilo::factory()->create();
        $this->dbManager->createStoreDatabase($store2);

        $response = $this->get("/store/{$store2->store_domain}/orders/{$this->order->id}/download/0", [
            'email' => 'test@example.com'
        ]);

        $response->assertNotFound();
    }

    /** @test */
    public function it_tracks_download_attempts()
    {
        $this->get("/store/{$this->store->store_domain}/orders/{$this->order->id}/download/0", [
            'email' => 'test@example.com'
        ]);

        $this->assertDatabaseHas('download_attempts', [
            'order_id' => $this->order->id,
            'success' => true
        ], $this->dbManager->getConnectionName($this->store));
    }
}
