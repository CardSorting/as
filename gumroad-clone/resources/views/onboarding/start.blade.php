<x-app-layout>
    <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Start Your Digital Store
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Create your store and start selling digital products today
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Special Trial Offer</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Get full access to all features for just $3 for your first month
                        </p>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="ml-3 text-sm text-gray-700">Unlimited products</span>
                        </div>
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="ml-3 text-sm text-gray-700">Custom domain</span>
                        </div>
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="ml-3 text-sm text-gray-700">Analytics & insights</span>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:px-6 -mx-4 sm:-mx-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">First month</p>
                                <p class="text-2xl font-bold text-gray-900">$3</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Then</p>
                                <p class="text-lg font-semibold text-gray-900">$29/month</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <a href="{{ route('onboarding.checkout') }}" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Start Your Trial
                        </a>
                    </div>

                    <div class="text-xs text-center text-gray-500">
                        By continuing, you agree to our terms of service and privacy policy.
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
