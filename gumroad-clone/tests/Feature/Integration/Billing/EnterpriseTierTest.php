<?php

namespace Tests\Feature\Integration\Billing;

use App\Models\StoreSilo;
use App\Models\Store\Product;
use App\Models\Store\Order;
use App\Services\StoreIsolation\DatabaseManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnterpriseTierTest extends TestCase
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
        
        $this->store = StoreSilo::factory()->create([
            'subscription_tier' => 'enterprise',
            'monthly_fee' => 999.99,
            'subscription_limits' => [
                'storage_mb' => null, // Unlimited
                'products' => null,   // Unlimited
                'monthly_revenue_cap' => null // Unlimited
            ],
            'custom_limits' => [
                'api_rate_limit' => 10000,
                'concurrent_users' => 1000,
                'custom_domain_count' => 10
            ]
        ]);
        
        $this->dbManager->createStoreDatabase($this->store);
        $this->connection->connect($this->store);
    }

    /** @test */
    public function it_allows_unlimited_products()
    {
        // Create large number of products
        for ($i = 0; $i < 1000; $i++) {
            Product::create([
                'name' => "Product {$i}",
                'price' => 10.00
            ]);
        }

        $this->assertEquals(1000, Product::count());
    }

    /** @test */
    public function it_allows_unlimited_storage()
    {
        $file = \Illuminate\Http\UploadedFile::fake()->create('huge.zip', 10000); // 10GB

        $product = Product::create([
            'name' => 'Massive Product',
            'price' => 10.00,
            'files' => [[
                'path' => $file->store('products', 'store_files'),
                'size' => $file->getSize(),
                'name' => 'huge.zip'
            ]]
        ]);

        $this->assertNotNull($product->id);
    }

    /** @test */
    public function it_enforces_custom_api_limits()
    {
        $this->store->incrementApiUsage(10001); // Exceed limit

        $this->expectException(\RuntimeException::class);
        
        $this->store->validateApiAccess();
    }

    /** @test */
    public function it_tracks_concurrent_users()
    {
        // Simulate concurrent users
        for ($i = 0; $i < 1000; $i++) {
            $this->store->trackActiveUser("user_{$i}");
        }

        $this->expectException(\RuntimeException::class);
        
        // Try to exceed limit
        $this->store->trackActiveUser("user_1001");
    }

    /** @test */
    public function it_allows_custom_domain_management()
    {
        // Add domains up to limit
        for ($i = 0; $i < 10; $i++) {
            $this->store->addCustomDomain("store{$i}.example.com");
        }

        $this->expectException(\RuntimeException::class);
        
        // Try to exceed domain limit
        $this->store->addCustomDomain("extra.example.com");
    }

    /** @test */
    public function it_provides_priority_support_access()
    {
        $this->assertTrue($this->store->hasPrioritySupport());
        $this->assertEquals(1, $this->store->getSupportPriorityLevel());
        $this->assertNotNull($this->store->getDedicatedSupportEmail());
    }

    /** @test */
    public function it_allows_custom_feature_flags()
    {
        $this->store->update([
            'custom_features' => [
                'beta_access' => true,
                'advanced_analytics' => true,
                'custom_checkout' => true
            ]
        ]);

        $features = $this->store->fresh()->getActiveFeatures();
        
        $this->assertTrue($features['beta_access']);
        $this->assertTrue($features['advanced_analytics']);
        $this->assertTrue($features['custom_checkout']);
    }
}
