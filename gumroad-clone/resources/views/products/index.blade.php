<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-semibold">Digital Products</h2>
                        @auth
                            <a href="{{ route('products.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Create Product
                            </a>
                        @endauth
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @forelse ($products as $product)
                            <div class="border rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-200">
                                @if($product->cover_image)
                                    <img src="{{ Storage::url($product->cover_image) }}" alt="{{ $product->name }}" class="w-full h-48 object-cover">
                                @else
                                    <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                                        <span class="text-gray-400">No Image</span>
                                    </div>
                                @endif
                                
                                <div class="p-4">
                                    <h3 class="text-lg font-semibold mb-2">{{ $product->name }}</h3>
                                    <p class="text-gray-600 text-sm mb-4">{{ Str::limit($product->description, 100) }}</p>
                                    
                                    <div class="flex justify-between items-center">
                                        <span class="text-lg font-bold">${{ number_format($product->price, 2) }}</span>
                                        <a href="{{ route('products.show', $product) }}" 
                                           class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm">
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-3 text-center py-12">
                                <p class="text-gray-500 text-lg">No products available.</p>
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-6">
                        {{ $products->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
