<?php

namespace Tests\Feature\Store\Lifecycle;

use App\Jobs\DeployStoreJob;
use App\Models\StoreSilo;
use App\Models\User;
use App\Services\DNS\DNSManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoreDeploymentTest extends TestCase
{
    use RefreshDatabase;

    private StoreSilo $store;
    private string $deployPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock DNS manager
        $this->mock(DNSManager::class, function ($mock) {
            $mock->shouldReceive('addStoreDomain')->once();
            $mock->shouldReceive('addCustomDomain')->never();
            $mock->shouldReceive('verifyDomain')->never();
        });

        // Create test directories
        Storage::fake('local');
        
        // Create test store
        $user = User::factory()->create();
        $this->store = StoreSilo::create([
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

        $this->deployPath = storage_path("store-deployments/{$this->store->id}");
    }

    /** @test */
    public function it_creates_deployment_directory_structure()
    {
        $job = new DeployStoreJob($this->store);
        $job->handle(app(\App\Services\StoreIsolation\StoreConnection::class), app(DNSManager::class));

        // Verify deployment directory exists
        $this->assertDirectoryExists($this->deployPath);
        $this->assertFileExists("{$this->deployPath}/.env");
        $this->assertFileExists("{$this->deployPath}/php-fpm.conf");
        $this->assertFileExists("{$this->deployPath}/nginx.conf");
    }

    /** @test */
    public function it_configures_environment_with_correct_settings()
    {
        $job = new DeployStoreJob($this->store);
        $job->handle(app(\App\Services\StoreIsolation\StoreConnection::class), app(DNSManager::class));

        $envContent = File::get("{$this->deployPath}/.env");

        // Verify environment settings
        $this->assertStringContainsString('APP_NAME="test-store"', $envContent);
        $this->assertStringContainsString('APP_ENV=production', $envContent);
        $this->assertStringContainsString('APP_DEBUG=false', $envContent);
        $this->assertStringContainsString('STORE_ID=' . $this->store->id, $envContent);
        $this->assertStringContainsString('SUBSCRIPTION_TIER=pro', $envContent);
        $this->assertStringContainsString('STORAGE_LIMIT=5000', $envContent);
        $this->assertStringContainsString('PRODUCTS_LIMIT=50', $envContent);
    }

    /** @test */
    public function it_configures_php_fpm_with_tier_specific_settings()
    {
        $job = new DeployStoreJob($this->store);
        $job->handle(app(\App\Services\StoreIsolation\StoreConnection::class), app(DNSManager::class));

        $fpmContent = File::get("{$this->deployPath}/php-fpm.conf");

        // Verify PHP-FPM settings
        $this->assertStringContainsString('[store-' . $this->store->id . ']', $fpmContent);
        $this->assertStringContainsString('memory_limit] = 512M', $fpmContent);
        $this->assertStringContainsString('pm.max_children = 10', $fpmContent);
        $this->assertStringContainsString('pm.start_servers = 5', $fpmContent);
    }

    /** @test */
    public function it_configures_nginx_with_correct_domain_and_ssl()
    {
        $job = new DeployStoreJob($this->store);
        $job->handle(app(\App\Services\StoreIsolation\StoreConnection::class), app(DNSManager::class));

        $nginxContent = File::get("{$this->deployPath}/nginx.conf");

        // Verify Nginx settings
        $this->assertStringContainsString('server_name test-store.' . config('store-domains.base_domain'), $nginxContent);
        $this->assertStringContainsString('ssl_certificate ' . config('store-domains.ssl.wildcard_cert'), $nginxContent);
        $this->assertStringContainsString('client_max_body_size 50M', $nginxContent);
    }

    /** @test */
    public function it_initializes_store_storage_directories()
    {
        $job = new DeployStoreJob($this->store);
        $job->handle(app(\App\Services\StoreIsolation\StoreConnection::class), app(DNSManager::class));

        $storagePath = Storage::disk('local')->path("store-{$this->store->id}");

        // Verify storage directories
        $this->assertDirectoryExists("{$storagePath}/products");
        $this->assertDirectoryExists("{$storagePath}/downloads");
        $this->assertDirectoryExists("{$storagePath}/media");

        // Verify symbolic link
        $this->assertDirectoryExists("{$this->deployPath}/public/storage");
        $this->assertTrue(File::isLink("{$this->deployPath}/public/storage"));
    }

    /** @test */
    public function it_handles_custom_domain_configuration()
    {
        // Update store with custom domain
        $this->store->update(['custom_domain' => 'custom.example.com']);

        // Update DNS manager mock
        $this->mock(DNSManager::class, function ($mock) {
            $mock->shouldReceive('addStoreDomain')->once();
            $mock->shouldReceive('addCustomDomain')
                ->once()
                ->with('custom.example.com', 'test-store');
            $mock->shouldReceive('verifyDomain')
                ->once()
                ->with('custom.example.com', 'test-store.' . config('store-domains.base_domain'))
                ->andReturn(true);
        });

        $job = new DeployStoreJob($this->store);
        $job->handle(app(\App\Services\StoreIsolation\StoreConnection::class), app(DNSManager::class));

        $nginxContent = File::get("{$this->deployPath}/nginx.conf");
        
        // Verify Nginx includes custom domain
        $this->assertStringContainsString('server_name test-store.' . config('store-domains.base_domain') . ' custom.example.com', $nginxContent);
    }

    /** @test */
    public function it_prevents_custom_domains_for_basic_tier()
    {
        // Update store to basic tier with custom domain
        $this->store->update([
            'subscription_tier' => 'basic',
            'custom_domain' => 'custom.example.com'
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Custom domains not allowed for this subscription tier');

        $job = new DeployStoreJob($this->store);
        $job->handle(app(\App\Services\StoreIsolation\StoreConnection::class), app(DNSManager::class));
    }

    /** @test */
    public function it_logs_deployment_progress()
    {
        Log::shouldReceive('info')->times(7)->withArgs(function ($message) {
            return in_array($message, [
                'Starting store deployment',
                'Setting up store environment',
                'Configuring store resources',
                'Deploying store application',
                'Initializing store storage',
                'Setting up store domain',
                'Running post-deployment tasks',
                'Starting store queue worker',
                'Store deployed successfully'
            ]);
        });

        $job = new DeployStoreJob($this->store);
        $job->handle(app(\App\Services\StoreIsolation\StoreConnection::class), app(DNSManager::class));
    }

    protected function tearDown(): void
    {
        // Clean up deployment directory
        if (File::exists($this->deployPath)) {
            File::deleteDirectory($this->deployPath);
        }

        parent::tearDown();
    }
}
