<?php

namespace Tests\Feature\Store\Products;

use App\Models\StoreSilo;
use App\Models\Store\Product;
use App\Services\StoreIsolation\DatabaseManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateProductTest extends TestCase
{
    use RefreshDatabase;

    private DatabaseManager $dbManager;
    private StoreConnection $connection;
    private StoreSilo $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dbManager = app(DatabaseManager::class);
        $this->connection = app(StoreConnection::class);
        
        $this->store = StoreSilo::factory()->create();
        $this->dbManager->createStoreDatabase($this->store);
        $this->connection->connect($this->store);
    }

    /** @test */
    public function it_creates_basic_product()
    {
        $product = Product::create([
            'name' => 'Simple Product',
            'price' => 10.00,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Simple Product',
            'price' => 10.00,
        ], $this->dbManager->getConnectionName($this->store));
    }

    /** @test */
    public function it_creates_product_with_description()
    {
        $product = Product::create([
            'name' => 'Detailed Product',
            'description' => 'Product description',
            'price' => 10.00,
        ]);

        $this->assertEquals('Product description', $product->description);
    }

    /** @test */
    public function it_creates_product_with_custom_currency()
    {
        $product = Product::create([
            'name' => 'EUR Product',
            'price' => 10.00,
            'currency' => 'EUR',
        ]);

        $this->assertEquals('EUR', $product->currency);
    }

    /** @test */
    public function it_defaults_to_usd_currency()
    {
        $product = Product::create([
            'name' => 'Default Currency Product',
            'price' => 10.00,
        ]);

        $this->assertEquals('USD', $product->currency);
    }
}
