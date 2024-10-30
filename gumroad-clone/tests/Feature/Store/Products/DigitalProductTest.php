<?php

namespace Tests\Feature\Store\Products;

use App\Models\StoreSilo;
use App\Models\Store\Product;
use App\Services\StoreIsolation\DatabaseManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class DigitalProductTest extends TestCase
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
    public function it_creates_digital_product_with_file()
    {
        $file = UploadedFile::fake()->create('test.pdf', 100);

        $product = Product::create([
            'name' => 'Digital Product',
            'price' => 10.00,
            'is_digital' => true,
            'files' => [[
                'path' => $file->store('products', 'store_files'),
                'name' => 'test.pdf',
                'size' => $file->getSize(),
                'type' => 'application/pdf'
            ]]
        ]);

        $this->assertTrue($product->is_digital);
        $this->assertCount(1, $product->files);
        $this->assertEquals('test.pdf', $product->files[0]['name']);
    }

    /** @test */
    public function it_stores_file_in_isolated_location()
    {
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

        $this->assertStringContainsString(
            (string) $this->store->id,
            $product->files[0]['path']
        );
    }

    /** @test */
    public function it_validates_file_type()
    {
        $file = UploadedFile::fake()->create('test.exe', 100);

        $this->expectException(\InvalidArgumentException::class);

        Product::create([
            'name' => 'Digital Product',
            'price' => 10.00,
            'is_digital' => true,
            'files' => [[
                'path' => $file->store('products', 'store_files'),
                'name' => 'test.exe',
                'size' => $file->getSize(),
                'type' => 'application/x-msdownload'
            ]]
        ]);
    }
}
