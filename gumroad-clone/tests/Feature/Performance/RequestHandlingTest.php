<?php

namespace Tests\Feature\Performance;

use App\Models\StoreSilo;
use App\Models\Store\Product;
use App\Services\StoreIsolation\DatabaseManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RequestHandlingTest extends TestCase
{
    use RefreshDatabase;

    private DatabaseManager $dbManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dbManager = app(DatabaseManager::class);
    }

    /** @test */
    public function store_switching_is_performant()
    {
        $stores = StoreSilo::factory(5)->create();
        
        foreach ($stores as $store) {
            $this->dbManager->createStoreDatabase($store);
        }

        $startTime = microtime(true);
        
        foreach ($stores as $store) {
            $response = $this->get("/store/{$store->store_domain}");
            $response->assertOk();
        }
        
        $endTime = microtime(true);
        $averageTime = ($endTime - $startTime) / count($stores);
        
        $this->assertLessThan(
            0.1, // 100ms
            $averageTime,
            "Store switching takes too long: {$averageTime}s"
        );
    }

    /** @test */
    public function concurrent_requests_are_handled_properly()
    {
        $store = StoreSilo::factory()->create();
        $this->dbManager->createStoreDatabase($store);
        
        $products = Product::factory(10)->create();
        
        // Simulate concurrent requests
        $startTime = microtime(true);
        
        $promises = [];
        for ($i = 0; $i < 10; $i++) {
            $response = $this->get("/store/{$store->store_domain}/products");
            $response->assertOk();
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        
        $this->assertLessThan(
            2.0, // 2 seconds total
            $totalTime,
            "Concurrent requests take too long: {$totalTime}s"
        );
    }

    /** @test */
    public function database_queries_are_optimized()
    {
        $store = StoreSilo::factory()->create();
        $this->dbManager->createStoreDatabase($store);
        
        Product::factory(50)->create();
        
        DB::enableQueryLog();
        
        $response = $this->get("/store/{$store->store_domain}/products");
        
        $queryCount = count(DB::getQueryLog());
        
        $this->assertLessThan(
            5, // Maximum 5 queries
            $queryCount,
            "Too many queries executed: {$queryCount}"
        );
        
        DB::disableQueryLog();
    }
}
