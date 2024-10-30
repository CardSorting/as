<?php

namespace Tests\Feature\Store\Settings;

use App\Models\StoreSilo;
use App\Models\Store\Settings;
use App\Services\StoreIsolation\DatabaseManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationSettingsTest extends TestCase
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
            'notification_config' => [
                'email' => [
                    'sales' => true,
                    'refunds' => false,
                    'low_inventory' => false
                ],
                'webhooks' => []
            ]
        ]);
    }

    /** @test */
    public function it_toggles_email_notifications()
    {
        $this->settings->update([
            'notification_config' => array_merge(
                $this->settings->notification_config,
                ['email' => [
                    'sales' => false,
                    'refunds' => true,
                    'low_inventory' => false
                ]]
            )
        ]);

        $notifications = $this->settings->fresh()->notification_config['email'];
        $this->assertFalse($notifications['sales']);
        $this->assertTrue($notifications['refunds']);
    }

    /** @test */
    public function it_manages_webhook_endpoints()
    {
        $webhooks = [
            [
                'url' => 'https://example.com/webhook',
                'events' => ['sale.completed', 'refund.processed']
            ]
        ];

        $this->settings->update([
            'notification_config' => array_merge(
                $this->settings->notification_config,
                ['webhooks' => $webhooks]
            )
        ]);

        $this->assertEquals(
            $webhooks,
            $this->settings->fresh()->notification_config['webhooks']
        );
    }

    /** @test */
    public function it_validates_webhook_urls()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->settings->update([
            'notification_config' => array_merge(
                $this->settings->notification_config,
                ['webhooks' => [
                    [
                        'url' => 'invalid-url',
                        'events' => ['sale.completed']
                    ]
                ]]
            )
        ]);
    }

    /** @test */
    public function it_validates_webhook_events()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->settings->update([
            'notification_config' => array_merge(
                $this->settings->notification_config,
                ['webhooks' => [
                    [
                        'url' => 'https://example.com/webhook',
                        'events' => ['invalid.event']
                    ]
                ]]
            )
        ]);
    }
}
