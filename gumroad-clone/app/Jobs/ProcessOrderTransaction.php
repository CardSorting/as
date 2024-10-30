<?php

namespace App\Jobs;

use App\Models\SiloTransaction;
use App\Models\StoreSilo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessOrderTransaction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $orderId;
    private $storeId;
    private $orderNumber;
    private $orderAmount;
    private $revenueSharePercentage;

    public function __construct(
        int $orderId,
        int $storeId,
        string $orderNumber,
        float $orderAmount,
        float $revenueSharePercentage
    ) {
        $this->orderId = $orderId;
        $this->storeId = $storeId;
        $this->orderNumber = $orderNumber;
        $this->orderAmount = $orderAmount;
        $this->revenueSharePercentage = $revenueSharePercentage;
    }

    public function handle(): void
    {
        // Calculate revenue share
        $adminShare = $this->orderAmount * ($this->revenueSharePercentage / 100);
        $storeShare = $this->orderAmount - $adminShare;

        // Process transactions in main database
        DB::setDefaultConnection('testing');

        // Create transactions and update balance in a single transaction
        DB::transaction(function () use ($storeShare, $adminShare) {
            // Create store share transaction
            DB::table('silo_transactions')->insert([
                'store_silo_id' => $this->storeId,
                'transaction_id' => $this->orderNumber,
                'amount' => $storeShare,
                'type' => 'store_share',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Create admin share transaction
            DB::table('silo_transactions')->insert([
                'store_silo_id' => $this->storeId,
                'transaction_id' => $this->orderNumber,
                'amount' => $adminShare,
                'type' => 'admin_share',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Update store balance
            DB::table('store_silos')
                ->where('id', $this->storeId)
                ->increment('available_balance', $storeShare);
        });
    }
}
