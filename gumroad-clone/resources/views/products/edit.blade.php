<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h2 class="text-2xl font-semibold mb-6">Edit Product</h2>

                    <form action="{{ route('products.update', $product) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <x-input-label for="name" value="Product Name" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" 
                                         :value="old('name', $product->name)" required autofocus />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>

                        <div>
                            <x-input-label for="description" value="Description" />
                            <textarea id="description" name="description" 
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    rows="4" required>{{ old('description', $product->description) }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('description')" />
                        </div>

                        <div>
                            <x-input-label for="price" value="Price ($)" />
                            <x-text-input id="price" name="price" type="number" step="0.01" min="0" 
                                         class="mt-1 block w-full" :value="old('price', $product->price)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('price')" />
                        </div>

                        <div>
                            <x-input-label for="cover_image" value="Cover Image" />
                            @if($product->cover_image)
                                <div class="mt-2 mb-4">
                                    <img src="{{ Storage::url($product->cover_image) }}" 
                                         alt="Current cover image" 
                                         class="w-48 h-32 object-cover rounded">
                                </div>
                            @endif
                            <input id="cover_image" name="cover_image" type="file" accept="image/*"
                                   class="mt-1 block w-full text-sm text-gray-500
                                          file:mr-4 file:py-2 file:px-4
                                          file:rounded-md file:border-0
                                          file:text-sm file:font-semibold
                                          file:bg-blue-50 file:text-blue-700
                                          hover:file:bg-blue-100" />
                            <p class="mt-1 text-sm text-gray-500">Leave empty to keep current image</p>
                            <x-input-error class="mt-2" :messages="$errors->get('cover_image')" />
                        </div>

                        <div>
                            <x-input-label for="file_path" value="Product File" />
                            @if($product->file_path)
                                <p class="mt-2 mb-4 text-sm text-gray-600">
                                    Current file: {{ basename($product->file_path) }}
                                </p>
                            @endif
                            <input id="file_path" name="file_path" type="file"
                                   class="mt-1 block w-full text-sm text-gray-500
                                          file:mr-4 file:py-2 file:px-4
                                          file:rounded-md file:border-0
                                          file:text-sm file:font-semibold
                                          file:bg-blue-50 file:text-blue-700
                                          hover:file:bg-blue-100" />
                            <p class="mt-1 text-sm text-gray-500">Leave empty to keep current file</p>
                            <x-input-error class="mt-2" :messages="$errors->get('file_path')" />
                        </div>

                        <div>
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="is_published" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                       {{ $product->is_published ? 'checked' : '' }}>
                                <span class="ms-2">Publish this product</span>
                            </label>
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>Update Product</x-primary-button>
                            <a href="{{ route('products.show', $product) }}" class="text-gray-600 hover:text-gray-900">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
