<?php

namespace Tests\Feature\Store\Settings;

use App\Models\StoreSilo;
use App\Models\Store\Settings;
use App\Services\StoreIsolation\DatabaseManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreCustomizationTest extends TestCase
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
            'store_config' => [
                'name' => 'Test Store',
                'description' => 'Test Description',
                'logo_url' => null,
                'social_links' => []
            ]
        ]);
    }

    /** @test */
    public function it_updates_store_name()
    {
        $this->settings->update([
            'store_config' => array_merge(
                $this->settings->store_config,
                ['name' => 'Updated Store Name']
            )
        ]);

        $this->assertEquals(
            'Updated Store Name',
            $this->settings->fresh()->store_config['name']
        );
    }

    /** @test */
    public function it_updates_store_description()
    {
        $this->settings->update([
            'store_config' => array_merge(
                $this->settings->store_config,
                ['description' => 'New store description']
            )
        ]);

        $this->assertEquals(
            'New store description',
            $this->settings->fresh()->store_config['description']
        );
    }

    /** @test */
    public function it_manages_social_links()
    {
        $socialLinks = [
            'twitter' => 'https://twitter.com/store',
            'instagram' => 'https://instagram.com/store'
        ];

        $this->settings->update([
            'store_config' => array_merge(
                $this->settings->store_config,
                ['social_links' => $socialLinks]
            )
        ]);

        $this->assertEquals(
            $socialLinks,
            $this->settings->fresh()->store_config['social_links']
        );
    }

    /** @test */
    public function it_validates_social_links()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->settings->update([
            'store_config' => array_merge(
                $this->settings->store_config,
                ['social_links' => [
                    'twitter' => 'invalid-url'
                ]]
            )
        ]);
    }
}
