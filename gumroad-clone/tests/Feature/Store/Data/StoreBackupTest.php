<?php

namespace Tests\Feature\Store\Data;

use App\Models\StoreSilo;
use App\Models\Store\Product;
use App\Models\Store\Order;
use App\Services\StoreIsolation\DatabaseManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoreBackupTest extends TestCase
{
    use RefreshDatabase;

    private DatabaseManager $dbManager;
    private StoreConnection $connection;
    private StoreSilo $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dbManager = app(DatabaseManager::class);
        $this->connection = app(StoreConnection::class);
        
        $this->store = StoreSilo::factory()->create();
        $this->dbManager->createStoreDatabase($this->store);
        $this->connection->connect($this->store);
        
        // Create test data
        Product::create([
            'name' => 'Test Product',
            'price' => 10.00
        ]);
    }

    /** @test */
    public function it_creates_isolated_database_backup()
    {
        $backupPath = $this->dbManager->createBackup($this->store);
        
        $this->assertTrue(Storage::exists($backupPath));
        $this->assertStringContainsString(
            (string) $this->store->id,
            $backupPath
        );
    }

    /** @test */
    public function it_includes_all_store_tables_in_backup()
    {
        $backupPath = $this->dbManager->createBackup($this->store);
        $backupContent = Storage::get($backupPath);
        
        $this->assertStringContainsString('products', $backupContent);
        $this->assertStringContainsString('orders', $backupContent);
        $this->assertStringContainsString('settings', $backupContent);
    }

    /** @test */
    public function it_excludes_other_store_data_from_backup()
    {
        // Create another store with data
        $store2 = StoreSilo::factory()->create();
        $this->dbManager->createStoreDatabase($store2);
        $this->connection->connect($store2);
        
        Product::create([
            'name' => 'Store 2 Product',
            'price' => 20.00
        ]);

        // Create backup of first store
        $backupPath = $this->dbManager->createBackup($this->store);
        $backupContent = Storage::get($backupPath);
        
        $this->assertStringNotContainsString('Store 2 Product', $backupContent);
    }

    /** @test */
    public function it_maintains_backup_isolation()
    {
        $backupPath = $this->dbManager->createBackup($this->store);
        
        // Ensure backup is stored in isolated location
        $this->assertStringContainsString(
            "store-backups/{$this->store->id}",
            $backupPath
        );
    }
}
