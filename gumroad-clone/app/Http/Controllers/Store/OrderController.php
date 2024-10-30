<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Store\Order;
use App\Models\Store\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('product')
            ->latest()
            ->paginate(20);

        return view('store.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        return view('store.orders.show', compact('order'));
    }

    public function checkout(Request $request, Product $product)
    {
        $validated = $request->validate([
            'customer_email' => 'required|email',
            'customer_name' => 'required|string',
        ]);

        $order = Order::create([
            'product_id' => $product->id,
            'order_number' => strtoupper(Str::random(10)),
            'amount' => $product->price,
            'currency' => $product->currency,
            'status' => 'pending',
            'customer_details' => [
                'email' => $validated['customer_email'],
                'name' => $validated['customer_name'],
            ],
        ]);

        // Here you would typically redirect to payment processing
        // For now, we'll just mark it as paid for demonstration
        $order->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return redirect()->route('store.orders.thank-you', $order);
    }

    public function thankYou(Order $order)
    {
        if ($order->status !== 'paid') {
            abort(404);
        }

        return view('store.orders.thank-you', compact('order'));
    }

    public function download(Order $order, $fileIndex)
    {
        if ($order->status !== 'paid') {
            abort(403, 'Order not paid');
        }

        $files = $order->product->files;
        if (!isset($files[$fileIndex])) {
            abort(404, 'File not found');
        }

        $file = $files[$fileIndex];
        return Storage::disk('store_files')->download(
            $file['path'],
            $file['name']
        );
    }
}
