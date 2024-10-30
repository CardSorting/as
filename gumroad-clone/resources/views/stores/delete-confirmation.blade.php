<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Delete Store') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-red-600">
                            {{ __('Warning: This action cannot be undone') }}
                        </h3>
                        <p class="mt-2 text-sm text-gray-600">
                            This will permanently delete your store "{{ $store->store_domain }}" and all its data, including:
                        </p>
                        <ul class="mt-2 list-disc list-inside text-sm text-gray-600">
                            <li>All products and their files</li>
                            <li>All order history and customer data</li>
                            <li>Store settings and configurations</li>
                            <li>Custom domain settings</li>
                        </ul>
                    </div>

                    <form method="POST" action="{{ route('stores.destroy', $store) }}" class="space-y-6">
                        @csrf
                        @method('DELETE')

                        <div>
                            <x-input-label for="confirmation" :value="__('Confirm Deletion')" />
                            <p class="mt-1 text-sm text-gray-600">
                                Please type <strong>{{ $store->store_domain }}</strong> to confirm deletion
                            </p>
                            <x-text-input
                                id="confirmation"
                                name="confirmation"
                                type="text"
                                class="mt-1 block w-full"
                                required
                            />
                            @error('confirmation')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-end">
                            <x-secondary-button type="button" onclick="window.history.back();" class="mr-3">
                                {{ __('Cancel') }}
                            </x-secondary-button>
                            <x-danger-button>
                                {{ __('Delete Store') }}
                            </x-danger-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
