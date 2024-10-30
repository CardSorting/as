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

class BasicDeploymentTest extends TestCase
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
    public function it_creates_basic_directory_structure()
    {
        // Mock dependencies
        $dns = $this->mock(DNSManager::class);
        $dns->shouldReceive('addStoreDomain')->once()->with('test-store');

        $connection = $this->mock(StoreConnection::class);
        $connection->shouldReceive('connect')->once()->with($this->store);

        // Mock logging
        Log::shouldReceive('info')->twice();
        Log::shouldReceive('error')->never();

        // Run deployment job
        $job = new DeployStoreJob($this->store);
        $result = $job->handle($connection, $dns);

        // Verify job completed
        $this->assertTrue($result);

        // Verify directories exist
        $this->assertDirectoryExists($this->deployPath);
        $this->assertDirectoryExists("{$this->deployPath}/public");
        $this->assertDirectoryExists("{$this->deployPath}/storage");

        // Verify directory permissions
        $this->assertEquals('0755', substr(sprintf('%o', fileperms($this->deployPath)), -4));
        $this->assertEquals('0755', substr(sprintf('%o', fileperms("{$this->deployPath}/public")), -4));
        $this->assertEquals('0755', substr(sprintf('%o', fileperms("{$this->deployPath}/storage")), -4));
    }

    protected function tearDown(): void
    {
        if (File::exists($this->deployPath)) {
            File::deleteDirectory($this->deployPath);
        }
        parent::tearDown();
    }
}
