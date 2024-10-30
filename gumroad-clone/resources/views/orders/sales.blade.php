@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">Sales Dashboard</h1>

    @if($orders->isEmpty())
        <div class="bg-white rounded-lg shadow p-6 text-center">
            <h2 class="text-xl font-semibold mb-2">No sales yet</h2>
            <p class="text-gray-600">You haven't made any sales yet</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-2">Total Sales</h3>
                <p class="text-2xl">${{ number_format($totalSales, 2) }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-2">Total Orders</h3>
                <p class="text-2xl">{{ $totalOrders }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-2">Average Order Value</h3>
                <p class="text-2xl">${{ number_format($averageOrderValue, 2) }}</p>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-4 border-b">
                <h2 class="text-lg font-semibold">Orders</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($orders as $order)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $order->created_at->format('Y-m-d H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $order->order_number }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $order->user->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $order->user->email }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $order->product->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $order->formatted_amount }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        @if($order->status === 'completed') bg-green-100 text-green-800
                                        @elseif($order->status === 'refunded') bg-red-100 text-red-800
                                        @else bg-yellow-100 text-yellow-800 @endif">
                                        {{ $order->status_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <a href="{{ route('orders.show', $order) }}" class="text-indigo-600 hover:text-indigo-900">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t">
                {{ $orders->links() }}
            </div>
        </div>
    @endif
</div>
@endsection
