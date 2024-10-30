<x-app-layout>
    <div class="min-h-screen bg-gray-50 py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
                    Let's Set Up Your Store
                </h2>
                <p class="mt-3 text-lg text-gray-500">
                    Follow these steps to get your store ready for customers
                </p>
            </div>

            <div class="space-y-8">
                <!-- Step 1: Store Details -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="h-8 w-8 rounded-full bg-indigo-600 flex items-center justify-center">
                                    <span class="text-white font-medium">1</span>
                                </div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Store Details</h3>
                                <p class="mt-1 text-sm text-gray-500">Set up your store name and branding</p>
                            </div>
                            <div class="ml-auto">
                                <button type="button" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                                    Start
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Add Products -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center">
                                    <span class="text-gray-600 font-medium">2</span>
                                </div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Add Products</h3>
                                <p class="mt-1 text-sm text-gray-500">Upload your digital products</p>
                            </div>
                            <div class="ml-auto">
                                <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50" disabled>
                                    Next
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Customize Store -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center">
                                    <span class="text-gray-600 font-medium">3</span>
                                </div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Customize Store</h3>
                                <p class="mt-1 text-sm text-gray-500">Personalize your store's appearance</p>
                            </div>
                            <div class="ml-auto">
                                <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50" disabled>
                                    Next
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Launch -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center">
                                    <span class="text-gray-600 font-medium">4</span>
                                </div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Launch Store</h3>
                                <p class="mt-1 text-sm text-gray-500">Review and publish your store</p>
                            </div>
                            <div class="ml-auto">
                                <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50" disabled>
                                    Launch
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
