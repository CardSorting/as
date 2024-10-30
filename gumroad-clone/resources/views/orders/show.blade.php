<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6">
                        <h2 class="text-2xl font-semibold">Order Details</h2>
                        <p class="text-gray-600">Order #{{ $order->id }}</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Order Information -->
                        <div class="space-y-6">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h3 class="text-lg font-semibold mb-4">Order Information</h3>
                                <dl class="space-y-2">
                                    <div class="flex justify-between">
                                        <dt class="text-gray-600">Status:</dt>
                                        <dd>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                       {{ $order->status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                {{ ucfirst($order->status) }}
                                            </span>
                                        </dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-gray-600">Purchase Date:</dt>
                                        <dd>{{ $order->created_at->format('M d, Y H:i') }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-gray-600">Amount Paid:</dt>
                                        <dd class="font-semibold">${{ number_format($order->amount, 2) }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-gray-600">Payment ID:</dt>
                                        <dd class="font-mono text-sm">{{ $order->payment_id }}</dd>
                                    </div>
                                </dl>
                            </div>

                            <!-- Product Information -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h3 class="text-lg font-semibold mb-4">Product Information</h3>
                                <div class="flex items-start space-x-4">
                                    @if($order->product->cover_image)
                                        <img src="{{ Storage::url($order->product->cover_image) }}" 
                                             alt="{{ $order->product->name }}"
                                             class="w-24 h-24 object-cover rounded">
                                    @else
                                        <div class="w-24 h-24 bg-gray-200 rounded flex items-center justify-center">
                                            <span class="text-gray-400 text-xs">No Image</span>
                                        </div>
                                    @endif
                                    <div>
                                        <h4 class="font-semibold">{{ $order->product->name }}</h4>
                                        <p class="text-sm text-gray-600">{{ Str::limit($order->product->description, 100) }}</p>
                                        <p class="text-sm text-gray-600 mt-2">Seller: {{ $order->product->user->name }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Download Section -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-lg font-semibold mb-4">Download Product</h3>
                            @if($order->status === 'completed')
                                <div class="text-center">
                                    <p class="mb-4">Your purchase was successful! You can now download your product.</p>
                                    <a href="{{ Storage::url($order->product->file_path) }}" 
                                       class="inline-flex items-center px-4 py-2 bg-blue-500 hover:bg-blue-700 text-white font-bold rounded-lg"
                                       download>
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                        Download
                                    </a>
                                </div>
                            @else
                                <div class="text-center text-gray-600">
                                    <p>Your order is still pending. Please wait for confirmation.</p>
                                    <p class="mt-2">Status: <span class="font-semibold">Pending</span></p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="mt-8">
                        <a href="{{ route('orders.index') }}" class="text-blue-600 hover:text-blue-900">
                            &larr; Back to Orders
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
