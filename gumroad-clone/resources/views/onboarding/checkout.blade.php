<x-app-layout>
    <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Complete Your Trial Setup
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                You're just one step away from starting your digital store
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                <form action="{{ route('onboarding.process') }}" method="POST" class="space-y-6">
                    @csrf
                    
                    <div class="bg-gray-50 px-4 py-5 sm:rounded-lg sm:p-6 -mx-4 sm:-mx-6">
                        <div class="space-y-4">
                            <div class="flex justify-between">
                                <div class="text-sm font-medium text-gray-900">Trial Period (30 days)</div>
                                <div class="text-sm font-medium text-gray-900">$3.00</div>
                            </div>
                            <div class="border-t border-gray-200 pt-4">
                                <div class="flex justify-between">
                                    <div class="text-sm font-medium text-gray-900">Total</div>
                                    <div class="text-lg font-bold text-gray-900">$3.00</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <label for="card-holder" class="block text-sm font-medium text-gray-700">
                                Card holder name
                            </label>
                            <div class="mt-1">
                                <input type="text" name="card-holder" id="card-holder" required class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                        </div>

                        <div>
                            <label for="card-number" class="block text-sm font-medium text-gray-700">
                                Card number
                            </label>
                            <div class="mt-1">
                                <input type="text" name="card-number" id="card-number" placeholder="1234 1234 1234 1234" required class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="expiry" class="block text-sm font-medium text-gray-700">
                                    Expiry date
                                </label>
                                <div class="mt-1">
                                    <input type="text" name="expiry" id="expiry" placeholder="MM/YY" required class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                            </div>

                            <div>
                                <label for="cvc" class="block text-sm font-medium text-gray-700">
                                    CVC
                                </label>
                                <div class="mt-1">
                                    <input type="text" name="cvc" id="cvc" placeholder="123" required class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                            </div>
                        </div>

                        <div>
                            <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Start Trial for $3
                            </button>
                        </div>

                        <div class="text-xs text-center text-gray-500">
                            Your card will be charged $3 now for the 30-day trial. After the trial, you'll be automatically charged $29/month unless you cancel.
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
