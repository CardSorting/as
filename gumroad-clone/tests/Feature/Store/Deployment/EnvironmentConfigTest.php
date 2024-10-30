<?php

namespace Tests\Feature\Store\Deployment;

use App\Jobs\DeployStoreJob;
use App\Models\StoreSilo;
use App\Models\User;
use App\Services\DNS\DNSManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class EnvironmentConfigTest extends TestCase
{
    use RefreshDatabase;

    private StoreSilo $store;
    private string $deployPath;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test store
        $user = User::factory()->create();
        $this->store = StoreSilo::create([
            'user_id' => $user->id,
            'store_domain' => 'test-store',
            'subscription_tier' => 'basic',
            'payment_status' => 'active',
            'monthly_fee' => 29.99,
            'subscription_limits' => [
                'storage_mb' => 1000,
                'products' => 10,
                'monthly_revenue_cap' => 50000.00
            ],
            'next_billing_date' => now()->addMonth(),
            'revenue_share_percentage' => 5.0,
            'available_balance' => 0
        ]);

        $this->deployPath = storage_path("store-deployments/{$this->store->id}");
        
        // Clean up any existing test directories
        if (File::exists($this->deployPath)) {
            File::deleteDirectory($this->deployPath);
        }
    }

    /** @test */
    public function it_configures_store_specific_settings()
    {
        // Mock dependencies
        $dns = $this->mock(DNSManager::class);
        $dns->shouldReceive('addStoreDomain')->once()->with('test-store');

        $connection = $this->mock(StoreConnection::class);
        $connection->shouldReceive('connect')->once()->with($this->store);

        // Run deployment job
        $job = new DeployStoreJob($this->store);
        $result = $job->handle($connection, $dns);

        // Verify job completed
        $this->assertTrue($result);

        // Read .env content
        $envPath = "{$this->deployPath}/.env";
        $this->assertFileExists($envPath);
        $envContent = File::get($envPath);

        // Debug output
        echo "\nActual .env content:\n" . $envContent . "\n";

        // Verify store-specific settings
        $expectedSettings = [
            'STORE_ID=' . $this->store->id,
            'SUBSCRIPTION_TIER=basic',
            'STORAGE_LIMIT=1000',
            'PRODUCTS_LIMIT=10',
            'REVENUE_CAP=50000.00'
        ];

        foreach ($expectedSettings as $setting) {
            $this->assertStringContainsString(
                $setting,
                $envContent,
                "Failed to find setting: {$setting}"
            );
        }
    }

    protected function tearDown(): void
    {
        if (File::exists($this->deployPath)) {
            File::deleteDirectory($this->deployPath);
        }
        parent::tearDown();
    }
}
