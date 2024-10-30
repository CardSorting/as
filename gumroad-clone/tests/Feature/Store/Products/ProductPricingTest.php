<?php

namespace Tests\Feature\Store\Products;

use App\Models\StoreSilo;
use App\Models\Store\Product;
use App\Services\StoreIsolation\DatabaseManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPricingTest extends TestCase
{
    use RefreshDatabase;

    private DatabaseManager $dbManager;
    private StoreConnection $connection;
    private StoreSilo $store;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dbManager = app(DatabaseManager::class);
        $this->connection = app(StoreConnection::class);
        
        $this->store = StoreSilo::factory()->create();
        $this->dbManager->createStoreDatabase($this->store);
        $this->connection->connect($this->store);
        
        $this->product = Product::create([
            'name' => 'Test Product',
            'price' => 10.00
        ]);
    }

    /** @test */
    public function it_updates_product_price()
    {
        $this->product->update(['price' => 15.00]);

        $this->assertEquals(15.00, $this->product->fresh()->price);
    }

    /** @test */
    public function it_validates_minimum_price()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->product->update(['price' => -1.00]);
    }

    /** @test */
    public function it_formats_price_with_currency()
    {
        $this->product->update([
            'price' => 15.00,
            'currency' => 'EUR'
        ]);

        $this->assertEquals('EUR 15.00', $this->product->getFormattedPrice());
    }

    /** @test */
    public function it_handles_zero_price_products()
    {
        $this->product->update(['price' => 0.00]);

        $this->assertEquals(0.00, $this->product->price);
        $this->assertEquals('USD 0.00', $this->product->getFormattedPrice());
    }
}
