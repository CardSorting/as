<?php

namespace App\Services\Billing;

use App\Models\Store\Order;
use App\Jobs\ProcessOrderPayment;

class PaymentProcessor
{
    public function processPayment(Order $order, array $paymentDetails): void
    {
        // Dispatch payment processing job
        ProcessOrderPayment::dispatch($order, $paymentDetails);
    }
}
