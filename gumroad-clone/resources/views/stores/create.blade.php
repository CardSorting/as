<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Your Store') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form method="POST" action="{{ route('stores.store') }}" class="space-y-6">
                        @csrf

                        <div>
                            <x-input-label for="domain" :value="__('Store Domain')" />
                            <div class="mt-1 flex rounded-md shadow-sm">
                                <input
                                    type="text"
                                    name="domain"
                                    id="domain"
                                    class="block w-full rounded-l-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    placeholder="your-store"
                                    value="{{ old('domain') }}"
                                    required
                                />
                                <span class="inline-flex items-center rounded-r-md border border-l-0 border-gray-300 bg-gray-50 px-3 text-gray-500 sm:text-sm">
                                    .{{ config('app.domain') }}
                                </span>
                            </div>
                            <p class="mt-2 text-sm text-gray-500">
                                This will be your store's URL. Use only lowercase letters, numbers, and hyphens.
                            </p>
                            @error('domain')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-end">
                            <x-primary-button>
                                {{ __('Create Store') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
