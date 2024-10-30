<?php

namespace Tests\Feature\Store\Settings;

use App\Models\StoreSilo;
use App\Models\Store\Settings;
use App\Services\StoreIsolation\DatabaseManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainSettingsTest extends TestCase
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
                'ssl_enabled' => false,
                'subdomain' => 'test-store',
                'domain_verification' => null
            ]
        ]);
    }

    /** @test */
    public function it_validates_custom_domain_format()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->settings->update([
            'domain_config' => array_merge(
                $this->settings->domain_config,
                ['custom_domain' => 'invalid domain']
            )
        ]);
    }

    /** @test */
    public function it_prevents_duplicate_domains()
    {
        // Create another store
        $store2 = StoreSilo::factory()->create();
        $this->dbManager->createStoreDatabase($store2);
        $this->connection->connect($store2);
        
        $settings2 = Settings::create([
            'domain_config' => [
                'custom_domain' => 'example.com'
            ]
        ]);

        $this->expectException(\RuntimeException::class);

        $this->settings->update([
            'domain_config' => array_merge(
                $this->settings->domain_config,
                ['custom_domain' => 'example.com']
            )
        ]);
    }

    /** @test */
    public function it_validates_subdomain_format()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->settings->update([
            'domain_config' => array_merge(
                $this->settings->domain_config,
                ['subdomain' => 'invalid.subdomain']
            )
        ]);
    }

    /** @test */
    public function it_prevents_duplicate_subdomains()
    {
        // Create another store
        $store2 = StoreSilo::factory()->create();
        $this->dbManager->createStoreDatabase($store2);
        $this->connection->connect($store2);
        
        $settings2 = Settings::create([
            'domain_config' => [
                'subdomain' => 'mystore'
            ]
        ]);

        $this->expectException(\RuntimeException::class);

        $this->settings->update([
            'domain_config' => array_merge(
                $this->settings->domain_config,
                ['subdomain' => 'mystore']
            )
        ]);
    }

    /** @test */
    public function it_manages_ssl_settings()
    {
        $this->settings->update([
            'domain_config' => array_merge(
                $this->settings->domain_config,
                [
                    'custom_domain' => 'example.com',
                    'ssl_enabled' => true
                ]
            )
        ]);

        $this->assertTrue($this->settings->fresh()->domain_config['ssl_enabled']);
    }

    /** @test */
    public function it_requires_domain_verification()
    {
        $this->settings->update([
            'domain_config' => array_merge(
                $this->settings->domain_config,
                [
                    'custom_domain' => 'example.com',
                    'domain_verification' => [
                        'token' => 'verification-token',
                        'verified_at' => now()
                    ]
                ]
            )
        ]);

        $this->assertNotNull(
            $this->settings->fresh()->domain_config['domain_verification']['verified_at']
        );
    }
}
