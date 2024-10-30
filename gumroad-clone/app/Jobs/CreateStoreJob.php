<?php

namespace App\Jobs;

use App\Models\StoreSilo;
use App\Services\StoreIsolation\DatabaseManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateStoreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $storeData;
    private $userId;

    public function __construct(array $storeData, int $userId)
    {
        $this->storeData = $storeData;
        $this->userId = $userId;
        
        // Set queue based on subscription tier
        $this->queue = config("store-queues.tiers.{$storeData['subscription_tier']}.queue");
    }

    public function handle(DatabaseManager $dbManager)
    {
        // Create store record
        $store = StoreSilo::create([
            'user_id' => $this->userId,
            'store_domain' => $this->storeData['domain'],
            'subscription_tier' => $this->storeData['subscription_tier'],
            'payment_status' => 'active',
            'monthly_fee' => $this->storeData['monthly_fee'],
            'subscription_limits' => $this->getSubscriptionLimits(),
            'next_billing_date' => now()->addMonth(),
            'revenue_share_percentage' => $this->getRevenueShare(),
            'available_balance' => 0
        ]);

        // Dispatch database creation job
        CreateStoreDatabaseJob::dispatch($store)
            ->onQueue(config('store-queues.store_operations.database'));

        // Dispatch deployment job
        DeployStoreJob::dispatch($store)
            ->onQueue(config('store-queues.store_operations.deployment'));

        return $store;
    }

    private function getSubscriptionLimits(): array
    {
        return match($this->storeData['subscription_tier']) {
            'basic' => [
                'storage_mb' => 1000,
                'products' => 10,
                'monthly_revenue_cap' => 50000.00
            ],
            'pro' => [
                'storage_mb' => 5000,
                'products' => 50,
                'monthly_revenue_cap' => 250000.00
            ],
            'enterprise' => [
                'storage_mb' => 25000,
                'products' => 'unlimited',
                'monthly_revenue_cap' => 'unlimited'
            ],
            default => throw new \InvalidArgumentException('Invalid subscription tier')
        };
    }

    private function getRevenueShare(): float
    {
        return match($this->storeData['subscription_tier']) {
            'basic' => 5.0, // 5% to admin
            'pro' => 3.0,   // 3% to admin
            'enterprise' => 1.0, // 1% to admin
            default => throw new \InvalidArgumentException('Invalid subscription tier')
        };
    }
}
