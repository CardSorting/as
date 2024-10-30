<?php

namespace Tests\Feature\Store\Settings;

use App\Models\StoreSilo;
use App\Models\Store\Settings;
use App\Services\StoreIsolation\DatabaseManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentSettingsTest extends TestCase
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
            'payment_config' => [
                'currency' => 'USD',
                'accepted_methods' => ['card'],
                'stripe_account_id' => null,
                'payout_schedule' => 'monthly'
            ]
        ]);
    }

    /** @test */
    public function it_updates_store_currency()
    {
        $this->settings->update([
            'payment_config' => array_merge(
                $this->settings->payment_config,
                ['currency' => 'EUR']
            )
        ]);

        $this->assertEquals(
            'EUR',
            $this->settings->fresh()->payment_config['currency']
        );
    }

    /** @test */
    public function it_validates_currency_code()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->settings->update([
            'payment_config' => array_merge(
                $this->settings->payment_config,
                ['currency' => 'INVALID']
            )
        ]);
    }

    /** @test */
    public function it_manages_payment_methods()
    {
        $this->settings->update([
            'payment_config' => array_merge(
                $this->settings->payment_config,
                ['accepted_methods' => ['card', 'paypal']]
            )
        ]);

        $this->assertContains(
            'paypal',
            $this->settings->fresh()->payment_config['accepted_methods']
        );
    }

    /** @test */
    public function it_validates_payment_methods()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->settings->update([
            'payment_config' => array_merge(
                $this->settings->payment_config,
                ['accepted_methods' => ['invalid_method']]
            )
        ]);
    }
}
