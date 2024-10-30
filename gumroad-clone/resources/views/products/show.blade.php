<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Product Image -->
                        <div>
                            @if($product->cover_image)
                                <img src="{{ Storage::url($product->cover_image) }}" 
                                     alt="{{ $product->name }}" 
                                     class="w-full rounded-lg shadow-md">
                            @else
                                <div class="w-full h-64 bg-gray-200 rounded-lg flex items-center justify-center">
                                    <span class="text-gray-400">No Image</span>
                                </div>
                            @endif
                        </div>

                        <!-- Product Details -->
                        <div class="space-y-6">
                            <h1 class="text-3xl font-bold text-gray-900">{{ $product->name }}</h1>
                            
                            <div class="text-2xl font-bold text-blue-600">
                                ${{ number_format($product->price, 2) }}
                            </div>

                            <div class="prose max-w-none">
                                <p>{{ $product->description }}</p>
                            </div>

                            <div class="border-t pt-6">
                                @if(Auth::check())
                                    @if(Auth::id() === $product->user_id)
                                        <div class="flex space-x-4">
                                            <a href="{{ route('products.edit', $product) }}" 
                                               class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                                Edit Product
                                            </a>
                                            <form action="{{ route('products.destroy', $product) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded"
                                                        onclick="return confirm('Are you sure you want to delete this product?')">
                                                    Delete Product
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <form action="{{ route('orders.store', $product) }}" method="POST">
                                            @csrf
                                            <button type="submit" 
                                                    class="w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg">
                                                Buy Now
                                            </button>
                                        </form>
                                    @endif
                                @else
                                    <a href="{{ route('login') }}" 
                                       class="block text-center bg-blue-500 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg">
                                        Login to Purchase
                                    </a>
                                @endif
                            </div>

                            <!-- Creator Info -->
                            <div class="mt-8 pt-6 border-t">
                                <h3 class="text-lg font-semibold">About the Creator</h3>
                                <div class="mt-2 flex items-center">
                                    <div class="text-gray-600">
                                        <p>{{ $product->user->name }}</p>
                                        <p class="text-sm">Member since {{ $product->user->created_at->format('M Y') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
