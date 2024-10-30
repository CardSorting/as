<?php

namespace App\Jobs;

use App\Models\Store\Order;
use App\Models\StoreSilo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessOrderPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $orderId;
    private $storeId;
    private $paymentDetails;
    private $storeData;

    public function __construct(Order $order, array $paymentDetails)
    {
        $store = $order->getStoreSilo();
        
        $this->orderId = $order->id;
        $this->storeId = $store->id;
        $this->paymentDetails = $paymentDetails;
        $this->storeData = [
            'payment_status' => $store->payment_status,
            'revenue_share_percentage' => $store->revenue_share_percentage
        ];
    }

    public function handle(): void
    {
        if ($this->storeData['payment_status'] !== 'active') {
            throw new \RuntimeException('Store payments are suspended');
        }

        // Switch to store database
        DB::setDefaultConnection("store_{$this->storeId}");
        $order = Order::findOrFail($this->orderId);

        if ($order->status === 'paid') {
            throw new \RuntimeException('Order is already paid');
        }

        // Cast amounts to float for comparison
        $orderAmount = (float) $order->amount;
        $paymentAmount = (float) ($this->paymentDetails['amount'] ?? 0);

        if ($orderAmount !== $paymentAmount) {
            throw new \RuntimeException('Payment amount mismatch');
        }

        // Update order status in store database
        DB::transaction(function () use ($order) {
            $order->update([
                'status' => isset($this->paymentDetails['error']) ? 'failed' : 'paid',
                'paid_at' => now(),
                'payment_details' => array_merge(
                    $this->paymentDetails,
                    ['processed_at' => now()->toIso8601String()]
                )
            ]);
        });

        if (!($this->paymentDetails['error'] ?? false)) {
            // Dispatch transaction processing job
            ProcessOrderTransaction::dispatch(
                $this->orderId,
                $this->storeId,
                $order->order_number,
                $orderAmount,
                $this->storeData['revenue_share_percentage']
            );
        }
    }
}
