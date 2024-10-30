<?php

namespace Tests\Feature\Store\Isolation;

use App\Models\StoreSilo;
use App\Models\User;
use App\Services\StoreIsolation\DatabaseManager;
use App\Services\StoreIsolation\StoreConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseIsolationTest extends TestCase
{
    use RefreshDatabase;

    private DatabaseManager $dbManager;
    private StoreConnection $connection;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user in main database
        $this->user = User::factory()->create();
        
        $this->dbManager = app(DatabaseManager::class);
        $this->connection = app(StoreConnection::class);
        
        // Clean up any existing test databases
        foreach (glob(database_path('stores/*.sqlite')) as $file) {
            unlink($file);
        }
    }

    /** @test */
    public function each_store_has_its_own_database_file()
    {
        $store1 = StoreSilo::factory()->create([
            'user_id' => $this->user->id
        ]);
        
        $store2 = StoreSilo::factory()->create([
            'user_id' => $this->user->id
        ]);

        $this->dbManager->createStoreDatabase($store1);
        $this->dbManager->createStoreDatabase($store2);

        $this->assertFileExists(database_path("stores/store_{$store1->id}.sqlite"));
        $this->assertFileExists(database_path("stores/store_{$store2->id}.sqlite"));
    }

    /** @test */
    public function stores_cannot_access_each_others_data()
    {
        // Create two stores
        $store1 = StoreSilo::factory()->create([
            'user_id' => $this->user->id
        ]);
        
        $store2 = StoreSilo::factory()->create([
            'user_id' => $this->user->id
        ]);

        $this->dbManager->createStoreDatabase($store1);
        $this->dbManager->createStoreDatabase($store2);

        try {
            // Connect to first store and create a product
            $this->connection->connect($store1);
            $this->connection->transaction(function () {
                DB::table('products')->insert([
                    'name' => 'Store 1 Product',
                    'price' => 10.00,
                    'is_digital' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            });
        } finally {
            $this->connection->disconnect();
        }

        try {
            // Connect to second store and verify it can't see first store's product
            $this->connection->connect($store2);
            $product = DB::table('products')->where('name', 'Store 1 Product')->first();
            $this->assertNull($product, 'Second store should not see first store\'s product');
        } finally {
            $this->connection->disconnect();
        }
    }

    /** @test */
    public function store_operations_are_isolated()
    {
        // Create two stores
        $store1 = StoreSilo::factory()->create([
            'user_id' => $this->user->id
        ]);
        
        $store2 = StoreSilo::factory()->create([
            'user_id' => $this->user->id
        ]);

        $this->dbManager->createStoreDatabase($store1);
        $this->dbManager->createStoreDatabase($store2);

        try {
            // Connect to first store and create schema
            $this->connection->connect($store1);
            $this->connection->transaction(function () {
                DB::statement('CREATE TABLE test_table (id INTEGER PRIMARY KEY, name TEXT)');
                DB::table('test_table')->insert(['name' => 'Test 1']);
            });
        } finally {
            $this->connection->disconnect();
        }

        try {
            // Connect to second store and verify schema doesn't exist
            $this->connection->connect($store2);
            $this->expectException(\PDOException::class);
            DB::table('test_table')->get();
        } finally {
            $this->connection->disconnect();
        }
    }

    protected function tearDown(): void
    {
        // Ensure we're disconnected from any store
        $this->connection->disconnect();
        
        // Clean up test databases
        foreach (glob(database_path('stores/*.sqlite')) as $file) {
            unlink($file);
        }
        
        parent::tearDown();
    }
}
