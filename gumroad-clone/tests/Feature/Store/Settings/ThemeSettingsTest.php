<?php

namespace Tests\Feature\Store\Settings;

use App\Models\StoreSilo;
use App\Models\Store\Settings;
use App\Services\StoreIsolation\DatabaseManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeSettingsTest extends TestCase
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
            'theme_config' => [
                'colors' => [
                    'primary' => '#000000',
                    'secondary' => '#ffffff'
                ],
                'fonts' => [
                    'heading' => 'Arial',
                    'body' => 'Helvetica'
                ]
            ]
        ]);
    }

    /** @test */
    public function it_updates_color_scheme()
    {
        $this->settings->update([
            'theme_config' => array_merge(
                $this->settings->theme_config,
                ['colors' => [
                    'primary' => '#FF0000',
                    'secondary' => '#00FF00'
                ]]
            )
        ]);

        $this->assertEquals('#FF0000', $this->settings->fresh()->theme_config['colors']['primary']);
    }

    /** @test */
    public function it_validates_color_format()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->settings->update([
            'theme_config' => array_merge(
                $this->settings->theme_config,
                ['colors' => [
                    'primary' => 'invalid-color'
                ]]
            )
        ]);
    }

    /** @test */
    public function it_updates_font_settings()
    {
        $this->settings->update([
            'theme_config' => array_merge(
                $this->settings->theme_config,
                ['fonts' => [
                    'heading' => 'Roboto',
                    'body' => 'Open Sans'
                ]]
            )
        ]);

        $this->assertEquals('Roboto', $this->settings->fresh()->theme_config['fonts']['heading']);
    }

    /** @test */
    public function it_maintains_theme_isolation()
    {
        // Create another store
        $store2 = StoreSilo::factory()->create();
        $this->dbManager->createStoreDatabase($store2);
        $this->connection->connect($store2);
        
        $settings2 = Settings::create([
            'theme_config' => [
                'colors' => [
                    'primary' => '#0000FF'
                ]
            ]
        ]);

        // Switch back to original store
        $this->connection->connect($this->store);
        
        $this->assertNotEquals(
            $settings2->theme_config['colors']['primary'],
            $this->settings->fresh()->theme_config['colors']['primary']
        );
    }
}
