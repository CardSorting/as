<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Auth::user()->orders()->with('product')->latest()->paginate(20);
        return view('orders.index', compact('orders'));
    }

    public function store(Request $request, Product $product)
    {
        if (!$product->is_published) {
            abort(404);
        }

        if (Auth::id() === $product->user_id) {
            abort(403, 'You cannot purchase your own product');
        }

        $request->validate([
            'amount' => ['sometimes', 'numeric', 'min:0', function ($attribute, $value, $fail) use ($product) {
                if ($value && $value != $product->price) {
                    $fail('The amount must match the product price.');
                }
            }]
        ]);

        $order = new Order([
            'user_id' => Auth::id(),
            'product_id' => $product->id,
            'amount' => $product->price,
            'status' => 'completed', // For demo purposes
            'payment_id' => 'demo_' . uniqid()
        ]);

        $order->save();

        return redirect()->route('orders.show', $order)
            ->with('success', 'Purchase completed successfully!');
    }

    public function show(Order $order)
    {
        if (Auth::id() !== $order->user_id && Auth::id() !== $order->product->user_id) {
            abort(403);
        }

        return view('orders.show', [
            'order' => $order->load(['user', 'product'])
        ]);
    }

    public function purchases()
    {
        $orders = Auth::user()->orders()
            ->with('product')
            ->latest()
            ->paginate(20);

        return view('orders.purchases', compact('orders'));
    }

    public function sales(Request $request)
    {
        $query = Order::whereIn('product_id', Auth::user()->products()->pluck('id'))
            ->with(['product', 'user']);

        // Apply filters
        if ($request->period) {
            $days = match($request->period) {
                '7days' => 7,
                '30days' => 30,
                default => null
            };
            if ($days) {
                $query->where('created_at', '>=', now()->subDays($days));
            }
        }

        if ($request->min_amount) {
            $query->where('amount', '>=', $request->min_amount);
        }

        if ($request->max_amount) {
            $query->where('amount', '<=', $request->max_amount);
        }

        // Apply sorting
        if ($request->sort && $request->order) {
            $query->orderBy($request->sort, $request->order);
        } else {
            $query->latest();
        }

        $orders = $query->paginate(20);

        $totalSales = $orders->where('status', 'completed')->sum('amount');
        $totalOrders = $orders->total();
        $averageOrderValue = $totalOrders > 0 ? $totalSales / $totalOrders : 0;

        if ($request->export === 'csv') {
            return $this->exportToCsv($orders);
        }

        return view('orders.sales', compact('orders', 'totalSales', 'totalOrders', 'averageOrderValue'));
    }

    public function update(Request $request, Order $order)
    {
        if (Auth::id() !== $order->product->user_id) {
            abort(403);
        }

        $request->validate([
            'status' => ['required', 'string', function ($attribute, $value, $fail) use ($order) {
                if (!$order->canTransitionTo($value)) {
                    $fail('Invalid status transition.');
                }
            }]
        ]);

        $order->update(['status' => $request->status]);

        return redirect()->back()->with('success', 'Order status updated successfully.');
    }

    public function download(Order $order)
    {
        if (Auth::id() !== $order->user_id) {
            abort(403);
        }

        if (!$order->canDownload()) {
            abort(403, 'Order must be completed to download the file.');
        }

        $path = $order->product->file_path;
        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'File not found.');
        }

        return Storage::disk('public')->download(
            $path,
            $order->product->name . '.' . pathinfo($path, PATHINFO_EXTENSION)
        );
    }

    protected function exportToCsv($orders)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=sales.csv',
        ];

        $callback = function() use ($orders) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Order ID', 'Date', 'Customer', 'Product', 'Amount', 'Status']);

            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->order_number,
                    $order->created_at->format('Y-m-d H:i:s'),
                    $order->user->name,
                    $order->product->name,
                    $order->formatted_amount,
                    $order->status_label
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}
