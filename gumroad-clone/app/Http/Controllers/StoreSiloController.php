<?php

namespace App\Http\Controllers;

use App\Models\StoreSilo;
use Illuminate\Http\Request;

class StoreSiloController extends Controller
{
    public function index()
    {
        $storeSilos = StoreSilo::with(['balance', 'recentTransactions' => function($query) {
            $query->latest('transaction_date')->limit(5);
        }])
        ->orderBy('store_domain')
        ->paginate(12);

        return view('admin.silo-monitor', compact('storeSilos'));
    }

    public function transactions(StoreSilo $silo)
    {
        $transactions = $silo->transactions()
            ->latest('transaction_date')
            ->paginate(50);

        return response()->json([
            'silo' => $silo->only('id', 'store_domain'),
            'transactions' => $transactions
        ]);
    }

    public function exportSiloData(StoreSilo $silo)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$silo->store_domain}-transactions.csv",
        ];

        $callback = function() use ($silo) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'Transaction ID',
                'Date',
                'Amount',
                'Status',
                'Paid Date'
            ]);

            $silo->transactions()
                ->orderBy('transaction_date', 'desc')
                ->chunk(1000, function($transactions) use($file) {
                    foreach ($transactions as $transaction) {
                        fputcsv($file, [
                            $transaction->transaction_id,
                            $transaction->transaction_date,
                            $transaction->amount,
                            $transaction->is_paid ? 'Paid' : 'Pending',
                            $transaction->paid_at
                        ]);
                    }
                });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
