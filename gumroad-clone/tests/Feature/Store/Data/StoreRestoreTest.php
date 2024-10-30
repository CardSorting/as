<?php

namespace Tests\Feature\Store\Data;

use App\Models\StoreSilo;
use App\Models\Store\Product;
use App\Models\Store\Order;
use App\Services\StoreIsolation\DatabaseManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreRestoreTest extends TestCase
{
    use RefreshDatabase;

    private DatabaseManager $dbManager;
    private StoreConnection $connection;
    private StoreSilo $store;
    private string $backupPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dbManager = app(DatabaseManager::class);
        $this->connection = app(StoreConnection::class);
        
        $this->store = StoreSilo::factory()->create();
        $this->dbManager->createStoreDatabase($this->store);
        $this->connection->connect($this->store);
        
        // Create test data and backup
        Product::create([
            'name' => 'Test Product',
            'price' => 10.00
        ]);
        
        $this->backupPath = $this->dbManager->createBackup($this->store);
    }

    /** @test */
    public function it_restores_store_data_in_isolation()
    {
        // Clear current data
        Product::query()->delete();
        
        $this->dbManager->restoreFromBackup($this->store, $this->backupPath);
        
        $this->assertDatabaseHas('products', [
            'name' => 'Test Product'
        ], $this->dbManager->getConnectionName($this->store));
    }

    /** @test */
    public function it_prevents_restore_to_wrong_store()
    {
        // Create another store
        $store2 = StoreSilo::factory()->create();
        $this->dbManager->createStoreDatabase($store2);
        
        $this->expectException(\RuntimeException::class);
        
        $this->dbManager->restoreFromBackup($store2, $this->backupPath);
    }

    /** @test */
    public function it_validates_backup_integrity()
    {
        $this->expectException(\RuntimeException::class);
        
        $this->dbManager->restoreFromBackup(
            $this->store,
            'invalid-backup-path.sql'
        );
    }

    /** @test */
    public function it_maintains_data_consistency_during_restore()
    {
        // Add more data after backup
        Product::create([
            'name' => 'New Product',
            'price' => 20.00
        ]);
        
        $this->dbManager->restoreFromBackup($this->store, $this->backupPath);
        
        // Should only have data from backup
        $this->assertDatabaseHas('products', [
            'name' => 'Test Product'
        ], $this->dbManager->getConnectionName($this->store));
        
        $this->assertDatabaseMissing('products', [
            'name' => 'New Product'
        ], $this->dbManager->getConnectionName($this->store));
    }
}
