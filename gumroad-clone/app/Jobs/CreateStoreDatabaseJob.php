<?php

namespace App\Jobs;

use App\Models\StoreSilo;
use App\Services\StoreIsolation\DatabaseManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateStoreDatabaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $store;

    public function __construct(StoreSilo $store)
    {
        $this->store = $store;
        
        // Set retries based on tier
        $this->tries = config("store-queues.tiers.{$store->subscription_tier}.tries");
        
        // Set timeout based on tier
        $this->timeout = config("store-queues.tiers.{$store->subscription_tier}.timeout");
    }

    public function handle(DatabaseManager $dbManager)
    {
        $dbManager->createStoreDatabase($this->store);
    }
}
