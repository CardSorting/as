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

class ServerConfigTest extends TestCase
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
    public function it_creates_php_fpm_config_with_basic_tier_limits()
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

        // Read PHP-FPM config
        $fpmPath = "{$this->deployPath}/php-fpm.conf";
        $this->assertFileExists($fpmPath);
        $fpmContent = File::get($fpmPath);

        // Debug output
        echo "\nActual PHP-FPM config:\n" . $fpmContent . "\n";

        // Verify basic tier PHP-FPM settings
        $this->assertStringContainsString('[store-' . $this->store->id . ']', $fpmContent);
        $this->assertStringContainsString('user = store-' . $this->store->id, $fpmContent);
        $this->assertStringContainsString('group = store-' . $this->store->id, $fpmContent);
        $this->assertStringContainsString('listen = /var/run/php-fpm/store-' . $this->store->id . '.sock', $fpmContent);
        
        // Process management settings
        $this->assertStringContainsString('pm = dynamic', $fpmContent);
        $this->assertStringContainsString('pm.max_children = 5', $fpmContent);
        
        // Resource limits
        $this->assertStringContainsString('php_admin_value[memory_limit] = 256M', $fpmContent);
        $this->assertStringContainsString('php_admin_value[upload_max_filesize] = 10M', $fpmContent);
        $this->assertStringContainsString('php_admin_value[post_max_size] = 10M', $fpmContent);
        
        // Security settings
        $this->assertStringContainsString('security.limit_extensions = .php', $fpmContent);
    }

    /** @test */
    public function it_creates_nginx_config_with_correct_domain()
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

        // Read Nginx config
        $nginxPath = "{$this->deployPath}/nginx.conf";
        $this->assertFileExists($nginxPath);
        $nginxContent = File::get($nginxPath);

        // Debug output
        echo "\nActual Nginx config:\n" . $nginxContent . "\n";

        // Verify Nginx settings
        $domain = $this->store->store_domain . '.' . config('store-domains.base_domain');
        $this->assertStringContainsString("server_name {$domain}", $nginxContent);
        $this->assertStringContainsString('listen 443 ssl http2', $nginxContent);
        $this->assertStringContainsString('ssl_certificate ' . config('store-domains.ssl.wildcard_cert'), $nginxContent);
        $this->assertStringContainsString('client_max_body_size 10M', $nginxContent);
        $this->assertStringContainsString('fastcgi_pass unix:/var/run/php-fpm/store-' . $this->store->id . '.sock', $nginxContent);
    }

    protected function tearDown(): void
    {
        if (File::exists($this->deployPath)) {
            File::deleteDirectory($this->deployPath);
        }
        parent::tearDown();
    }
}
