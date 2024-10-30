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

class StorageSetupTest extends TestCase
{
    use RefreshDatabase;

    private StoreSilo $store;
    private string $deployPath;
    private string $storagePath;

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
        $this->storagePath = storage_path("local/store-{$this->store->id}");
        
        // Clean up any existing test directories
        foreach ([$this->deployPath, $this->storagePath] as $path) {
            if (File::exists($path)) {
                File::deleteDirectory($path);
            }
        }
    }

    /** @test */
    public function it_creates_required_storage_directories()
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

        // Debug output
        echo "\nDeployment path: {$this->deployPath}";
        echo "\nStorage path: {$this->storagePath}";
        
        // List all directories in storage
        echo "\n\nExisting directories:";
        $this->listDirectories(storage_path());

        // Verify storage directories exist
        $requiredDirs = ['products', 'downloads', 'media'];
        foreach ($requiredDirs as $dir) {
            $path = "{$this->storagePath}/{$dir}";
            $this->assertDirectoryExists($path, "Directory {$dir} not found");
            
            // Debug output
            echo "\nVerifying directory: {$path}";
            if (File::exists($path)) {
                echo "\nPermissions: " . substr(sprintf('%o', fileperms($path)), -4);
            } else {
                echo "\nDirectory does not exist";
            }
        }
    }

    private function listDirectories(string $path, string $indent = ''): void
    {
        if (!File::exists($path)) {
            echo "\n{$indent}Path does not exist: {$path}";
            return;
        }

        $files = File::directories($path);
        foreach ($files as $file) {
            echo "\n{$indent}" . basename($file);
            $this->listDirectories($file, $indent . '  ');
        }
    }

    /** @test */
    public function it_sets_correct_storage_permissions()
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

        // Verify directory permissions
        $requiredDirs = ['products', 'downloads', 'media'];
        foreach ($requiredDirs as $dir) {
            $path = "{$this->storagePath}/{$dir}";
            $perms = substr(sprintf('%o', fileperms($path)), -4);
            
            // Debug output
            echo "\nChecking permissions for {$dir}: {$perms}";
            
            // Verify permissions (0755 for directories)
            $this->assertEquals('0755', $perms, "Wrong permissions for {$dir}");
            $this->assertTrue(is_writable($path), "Directory {$dir} is not writable");
        }
    }

    /** @test */
    public function it_creates_storage_symbolic_link()
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

        // Verify symbolic link
        $linkPath = "{$this->deployPath}/public/storage";
        
        // Debug output
        echo "\nChecking symbolic link:";
        echo "\nLink path: {$linkPath}";
        if (file_exists($linkPath)) {
            echo "\nTarget path: " . readlink($linkPath);
        } else {
            echo "\nLink does not exist";
        }
        
        $this->assertTrue(is_link($linkPath), "Storage symbolic link not created");
        $this->assertEquals(
            $this->storagePath,
            readlink($linkPath),
            "Symbolic link points to wrong location"
        );
    }

    protected function tearDown(): void
    {
        // Clean up test directories
        foreach ([$this->deployPath, $this->storagePath] as $path) {
            if (File::exists($path)) {
                File::deleteDirectory($path);
            }
        }
        parent::tearDown();
    }
}
