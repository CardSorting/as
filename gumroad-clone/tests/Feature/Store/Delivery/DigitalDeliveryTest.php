<?php

namespace Tests\Feature\Store\Delivery;

use App\Models\StoreSilo;
use App\Models\Store\Product;
use App\Models\Store\Order;
use App\Services\StoreIsolation\DatabaseManager;
use App\Services\StoreIsolation\StoreConnection;
use App\Services\SecureFileServer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class DigitalDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private DatabaseManager $dbManager;
    private StoreConnection $connection;
    private StoreSilo $store;
    private Product $product;
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
        
        $this->product = Product::create([
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
            'product_id' => $this->product->id,
            'order_number' => 'ORD-' . uniqid(),
            'amount' => $this->product->price,
            'status' => 'pending',
            'customer_details' => ['email' => 'test@example.com']
        ]);
    }

    /** @test */
    public function it_prevents_download_before_payment()
    {
        $this->expectException(\RuntimeException::class);

        app(SecureFileServer::class)->serveFile($this->order, 0);
    }

    /** @test */
    public function it_allows_download_after_payment()
    {
        $this->order->update([
            'status' => 'paid',
            'paid_at' => now()
        ]);

        $response = app(SecureFileServer::class)->serveFile($this->order, 0);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            'application/pdf',
            $response->headers->get('Content-Type')
        );
    }

    /** @test */
    public function it_validates_file_index()
    {
        $this->order->update([
            'status' => 'paid',
            'paid_at' => now()
        ]);

        $this->expectException(\RuntimeException::class);

        app(SecureFileServer::class)->serveFile($this->order, 999);
    }

    /** @test */
    public function it_serves_files_from_correct_store_path()
    {
        $this->order->update([
            'status' => 'paid',
            'paid_at' => now()
        ]);

        $response = app(SecureFileServer::class)->serveFile($this->order, 0);

        $this->assertStringContainsString(
            (string) $this->store->id,
            $response->getFile()->getPathname()
        );
    }
}
