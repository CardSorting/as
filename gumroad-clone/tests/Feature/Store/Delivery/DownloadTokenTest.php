<?php

namespace Tests\Feature\Store\Delivery;

use App\Models\StoreSilo;
use App\Models\Store\Product;
use App\Models\Store\Order;
use App\Models\Store\DownloadToken;
use App\Services\StoreIsolation\DatabaseManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DownloadTokenTest extends TestCase
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
        
        $product = Product::create([
            'name' => 'Digital Product',
            'price' => 10.00,
            'is_digital' => true
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
    public function it_creates_download_token()
    {
        $token = DownloadToken::create([
            'order_id' => $this->order->id,
            'token' => bin2hex(random_bytes(32)),
            'expires_at' => now()->addDays(7),
            'remaining_downloads' => 5
        ]);

        $this->assertDatabaseHas('download_tokens', [
            'id' => $token->id,
            'order_id' => $this->order->id
        ], $this->dbManager->getConnectionName($this->store));
    }

    /** @test */
    public function it_validates_token_expiry()
    {
        $token = DownloadToken::create([
            'order_id' => $this->order->id,
            'token' => bin2hex(random_bytes(32)),
            'expires_at' => now()->subDay(),
            'remaining_downloads' => 5
        ]);

        $this->assertFalse($token->isValid());
    }

    /** @test */
    public function it_tracks_remaining_downloads()
    {
        $token = DownloadToken::create([
            'order_id' => $this->order->id,
            'token' => bin2hex(random_bytes(32)),
            'expires_at' => now()->addDays(7),
            'remaining_downloads' => 1
        ]);

        $token->decrementDownloads();

        $this->assertFalse($token->fresh()->isValid());
    }

    /** @test */
    public function it_isolates_tokens_per_store()
    {
        $token = DownloadToken::create([
            'order_id' => $this->order->id,
            'token' => bin2hex(random_bytes(32)),
            'expires_at' => now()->addDays(7)
        ]);

        // Create another store
        $store2 = StoreSilo::factory()->create();
        $this->dbManager->createStoreDatabase($store2);
        $this->connection->connect($store2);

        $this->assertDatabaseMissing('download_tokens', [
            'token' => $token->token
        ], $this->dbManager->getConnectionName($store2));
    }
}
