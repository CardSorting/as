<?php

namespace Tests\Feature\Store\Settings;

use App\Models\StoreSilo;
use App\Models\Store\Settings;
use App\Services\StoreIsolation\DatabaseManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreUrlTest extends TestCase
{
    use RefreshDatabase;

    private DatabaseManager $dbManager;
    private StoreConnection $connection;
    private StoreSilo $store;
    private Settings $settings;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dbManager = app(DatabaseManager::class);
        $this->connection = app(StoreConnection::class);
        
        $this->store = StoreSilo::factory()->create();
        $this->dbManager->createStoreDatabase($this->store);
        $this->connection->connect($this->store);
        
        $this->settings = Settings::create([
            'domain_config' => [
                'custom_domain' => null,
                'subdomain' => 'test-store'
            ]
        ]);
    }

    /** @test */
    public function it_generates_store_url_with_subdomain()
    {
        $url = $this->store->getStoreUrl();
        
        $this->assertEquals(
            "https://test-store." . config('app.domain'),
            $url
        );
    }

    /** @test */
    public function it_uses_custom_domain_when_available()
    {
        $this->settings->update([
            'domain_config' => array_merge(
                $this->settings->domain_config,
                [
                    'custom_domain' => 'example.com',
                    'domain_verification' => [
                        'verified_at' => now()
                    ]
                ]
            )
        ]);

        $url = $this->store->getStoreUrl();
        
        $this->assertEquals('https://example.com', $url);
    }

    /** @test */
    public function it_requires_verification_for_custom_domain()
    {
        $this->settings->update([
            'domain_config' => array_merge(
                $this->settings->domain_config,
                ['custom_domain' => 'example.com']
            )
        ]);

        $url = $this->store->getStoreUrl();
        
        // Should fall back to subdomain if custom domain not verified
        $this->assertEquals(
            "https://test-store." . config('app.domain'),
            $url
        );
    }

    /** @test */
    public function it_handles_ssl_configuration()
    {
        $this->settings->update([
            'domain_config' => array_merge(
                $this->settings->domain_config,
                ['ssl_enabled' => false]
            )
        ]);

        $url = $this->store->getStoreUrl();
        
        $this->assertStringStartsWith('http://', $url);
    }
}
