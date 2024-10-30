<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Dashboard') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if($stores->isEmpty())
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-8 text-center">
                        <div class="mx-auto h-12 w-12 text-gray-400">
                            <svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">Welcome to Your Digital Store Journey</h3>
                        <p class="mt-2 text-sm text-gray-500 max-w-sm mx-auto">
                            Start your journey with a 30-day trial for just $3. Get access to all premium features and start selling your digital products today.
                        </p>
                        <div class="mt-6">
                            <a href="{{ route('onboarding.start') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Start Your 30-Day Trial
                            </a>
                        </div>
                        <p class="mt-4 text-xs text-gray-500">
                            No commitment required. Cancel anytime during the trial.
                        </p>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($stores as $store)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900">{{ $store->name }}</h3>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $store->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ ucfirst($store->status) }}
                                    </span>
                                </div>
                                <div class="space-y-2">
                                    <p class="text-sm text-gray-600">{{ $store->description }}</p>
                                    <div class="flex justify-between text-sm text-gray-500">
                                        <span>Products: {{ $store->products_count ?? 0 }}</span>
                                        <span>Sales: {{ $store->sales_count ?? 0 }}</span>
                                    </div>
                                </div>
                                <div class="mt-4 flex justify-end space-x-2">
                                    <a href="#" class="text-sm text-indigo-600 hover:text-indigo-900">View Store</a>
                                    <a href="#" class="text-sm text-indigo-600 hover:text-indigo-900">Manage</a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
