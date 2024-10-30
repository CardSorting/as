<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Store Silos Monitor') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Store Silos Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($storeSilos as $silo)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-lg font-semibold">{{ $silo->store_domain }}</h3>
                                    <p class="text-sm text-gray-500">ID: {{ $silo->id }}</p>
                                </div>
                                <a href="#" onclick="viewTransactions({{ $silo->id }})" class="text-blue-600 hover:text-blue-800 text-sm">
                                    View Transactions
                                </a>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <div class="text-sm text-gray-600">Current Balance</div>
                                    <div class="text-2xl font-bold">${{ number_format($silo->balance->current_balance, 2) }}</div>
                                </div>

                                <div>
                                    <div class="text-sm text-gray-600">Lifetime Earnings</div>
                                    <div class="text-lg">${{ number_format($silo->balance->lifetime_earnings, 2) }}</div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <div class="text-sm text-gray-600">Last Transaction</div>
                                        <div class="text-sm">
                                            @if($silo->balance->last_transaction_at)
                                                {{ $silo->balance->last_transaction_at->format('M d, Y') }}
                                            @else
                                                Never
                                            @endif
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-600">Last Payout</div>
                                        <div class="text-sm">
                                            @if($silo->balance->last_payout_at)
                                                {{ $silo->balance->last_payout_at->format('M d, Y') }}
                                            @else
                                                Never
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="border-t pt-4">
                                    <div class="text-sm text-gray-600 mb-2">Recent Activity</div>
                                    <div class="space-y-2">
                                        @forelse($silo->recentTransactions as $transaction)
                                            <div class="flex justify-between items-center text-sm">
                                                <div>
                                                    <span class="font-mono">${{ number_format($transaction->amount, 2) }}</span>
                                                    <span class="text-gray-500 text-xs">{{ $transaction->transaction_date->format('M d') }}</span>
                                                </div>
                                                <div>
                                                    @if($transaction->is_paid)
                                                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Paid</span>
                                                    @else
                                                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Pending</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @empty
                                            <div class="text-sm text-gray-500">No recent transactions</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $storeSilos->links() }}
            </div>
        </div>
    </div>

    <!-- Transaction Modal -->
    <div id="transaction-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Store Transactions</h3>
                <button onclick="closeModal()" class="text-gray-600 hover:text-gray-800">&times;</button>
            </div>
            <div id="transaction-content">
                <!-- Transaction data will be loaded here -->
            </div>
        </div>
    </div>
</x-admin-layout>
