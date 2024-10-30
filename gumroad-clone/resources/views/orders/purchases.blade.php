@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">My Purchases</h1>

    @if($orders->isEmpty())
        <div class="bg-white rounded-lg shadow p-6 text-center">
            <h2 class="text-xl font-semibold mb-2">No purchases yet</h2>
            <p class="text-gray-600">You haven't made any purchases yet</p>
        </div>
    @else
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
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
                                    @if($order->canDownload())
                                        <a href="{{ route('orders.download', $order) }}" class="ml-4 text-indigo-600 hover:text-indigo-900">Download</a>
                                    @endif
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
