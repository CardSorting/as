<?php

namespace Tests\Feature\Store\Lifecycle;

use App\Jobs\CreateStoreJob;
use App\Jobs\CreateStoreDatabaseJob;
use App\Jobs\DeployStoreJob;
use App\Models\User;
use App\Models\StoreSilo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class StoreCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test directories
        Storage::fake('local');
        Storage::fake('store-deployments');
    }

    /** @test */
    public function it_creates_store_with_tier_based_queuing()
    {
        Queue::fake();

        $user = User::factory()->create();
        
        $storeData = [
            'domain' => 'test-store',
            'subscription_tier' => 'pro',
            'monthly_fee' => 49.99
        ];

        // Dispatch store creation job
        $job = new CreateStoreJob($storeData, $user->id);
        $job->handle(app(\App\Services\StoreIsolation\DatabaseManager::class));

        // Verify store was created
        $store = StoreSilo::first();
        $this->assertNotNull($store);
        $this->assertEquals('test-store', $store->store_domain);
        $this->assertEquals('pro', $store->subscription_tier);

        // Verify jobs were dispatched to correct queues
        Queue::assertPushedOn('store-database', CreateStoreDatabaseJob::class);
        Queue::assertPushedOn('store-deployment', DeployStoreJob::class);

        // Verify database job configuration
        Queue::assertPushed(CreateStoreDatabaseJob::class, function ($job) {
            return $job->tries === config('store-queues.tiers.pro.tries')
                && $job->timeout === config('store-queues.tiers.pro.timeout');
        });

        // Verify deployment job configuration
        Queue::assertPushed(DeployStoreJob::class, function ($job) {
            return $job->tries === config('store-queues.tiers.pro.tries')
                && $job->timeout === config('store-queues.tiers.pro.timeout');
        });
    }

    /** @test */
    public function it_deploys_store_with_correct_configuration()
    {
        $user = User::factory()->create();
        $store = StoreSilo::create([
            'user_id' => $user->id,
            'store_domain' => 'test-store',
            'subscription_tier' => 'pro',
            'payment_status' => 'active',
            'monthly_fee' => 49.99,
            'subscription_limits' => [
                'storage_mb' => 5000,
                'products' => 50,
                'monthly_revenue_cap' => 250000.00
            ],
            'next_billing_date' => now()->addMonth(),
            'revenue_share_percentage' => 3.0,
            'available_balance' => 0
        ]);

        // Deploy store
        $job = new DeployStoreJob($store);
        $job->handle(app(\App\Services\StoreIsolation\StoreConnection::class));

        // Verify deployment directory structure
        $deployPath = storage_path("store-deployments/{$store->id}");
        $this->assertDirectoryExists($deployPath);
        $this->assertFileExists("{$deployPath}/.env");
        $this->assertFileExists("{$deployPath}/php-fpm.conf");
        $this->assertFileExists("{$deployPath}/nginx.conf");

        // Verify environment configuration
        $envContent = File::get("{$deployPath}/.env");
        $this->assertStringContainsString('APP_NAME="test-store"', $envContent);
        $this->assertStringContainsString('SUBSCRIPTION_TIER=pro', $envContent);
        $this->assertStringContainsString('STORAGE_LIMIT=5000', $envContent);

        // Verify PHP-FPM configuration
        $fpmContent = File::get("{$deployPath}/php-fpm.conf");
        $this->assertStringContainsString('memory_limit] = 512M', $fpmContent);
        $this->assertStringContainsString('max_children = 10', $fpmContent);

        // Verify Nginx configuration
        $nginxContent = File::get("{$deployPath}/nginx.conf");
        $this->assertStringContainsString('server_name test-store.' . config('app.domain'), $nginxContent);
        $this->assertStringContainsString('ssl_certificate /etc/ssl/certs/wildcard.' . config('app.domain') . '.pem', $nginxContent);
        $this->assertStringContainsString('client_max_body_size 50M', $nginxContent);

        // Verify storage directories
        $storagePath = Storage::disk('local')->path("store-{$store->id}");
        $this->assertDirectoryExists("{$storagePath}/products");
        $this->assertDirectoryExists("{$storagePath}/downloads");
        $this->assertDirectoryExists("{$storagePath}/media");
    }

    protected function tearDown(): void
    {
        // Clean up test directories
        Storage::fake('local');
        Storage::fake('store-deployments');
        
        parent::tearDown();
    }
}
