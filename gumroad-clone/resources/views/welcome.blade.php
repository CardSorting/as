<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="The all-in-one platform for creators to sell digital products, manage customers, and grow their business">
    <title>{{ config('app.name', 'Digital Marketplace') }} - Sell Digital Products Online</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased font-sans text-gray-900 scroll-smooth">
    <!-- Header Navigation -->
    <header class="bg-white/95 backdrop-blur-sm shadow-sm fixed w-full z-50 transition-all duration-300">
        <nav class="container mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <a href="/" class="text-2xl font-bold text-indigo-600 hover:text-indigo-700 transition-colors">
                        MarketFlow
                    </a>
                </div>
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#features" class="text-gray-600 hover:text-gray-900 transition-colors">Features</a>
                    <a href="#pricing" class="text-gray-600 hover:text-gray-900 transition-colors">Pricing</a>
                    <a href="#examples" class="text-gray-600 hover:text-gray-900 transition-colors">Examples</a>
                    <a href="{{ route('login') }}" class="text-gray-600 hover:text-gray-900 transition-colors">Login</a>
                    <a href="{{ route('register') }}" class="btn-primary">Start Selling</a>
                </div>
                <button type="button" class="md:hidden inline-flex items-center justify-center p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500" aria-controls="mobile-menu" aria-expanded="false">
                    <span class="sr-only">Open main menu</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
            <div class="md:hidden hidden" id="mobile-menu">
                <div class="pt-2 pb-3 space-y-1">
                    <a href="#features" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">Features</a>
                    <a href="#pricing" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">Pricing</a>
                    <a href="#examples" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">Examples</a>
                    <a href="{{ route('login') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">Login</a>
                    <a href="{{ route('register') }}" class="block w-full px-4 py-2 text-center font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-md">Start Selling</a>
                </div>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="relative min-h-screen flex items-center">
        <div class="absolute inset-0 bg-gradient-to-br from-indigo-50 via-white to-indigo-50/50"></div>
        <div class="relative container mx-auto px-4 sm:px-6 lg:px-8 py-32">
            <div class="max-w-4xl mx-auto text-center">
                <div class="animate-fade-in">
                    <h1 class="text-5xl sm:text-6xl lg:text-7xl font-extrabold text-gray-900 tracking-tight mb-8">
                        Turn Your Digital Content Into a 
                        <span class="bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 to-violet-600">Thriving Business</span>
                    </h1>
                    <p class="text-xl sm:text-2xl text-gray-600 mb-8 max-w-3xl mx-auto leading-relaxed">
                        Join thousands of creators earning 
                        <span class="font-semibold text-gray-900">$100K+</span> 
                        annually selling digital products. No technical skills required.
                    </p>
                    <div class="flex flex-col sm:flex-row justify-center gap-4 mb-12">
                        <a href="{{ route('register') }}" class="btn-primary group">
                            Start Your Store
                            <svg class="ml-2 -mr-1 w-5 h-5 group-hover:translate-x-1 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </a>
                        <a href="#features" class="btn-secondary">
                            See How It Works
                        </a>
                    </div>
                    <!-- Trust Indicators -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-8 items-center justify-center max-w-3xl mx-auto">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-gray-900 mb-1">50K+</div>
                            <div class="text-sm text-gray-600">Active Sellers</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-gray-900 mb-1">$200M+</div>
                            <div class="text-sm text-gray-600">Creator Earnings</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-gray-900 mb-1">2M+</div>
                            <div class="text-sm text-gray-600">Happy Customers</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-gray-900 mb-1">99.9%</div>
                            <div class="text-sm text-gray-600">Uptime</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-white">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-base text-indigo-600 font-semibold tracking-wide uppercase mb-3">Features</h2>
                <p class="text-4xl lg:text-5xl font-bold text-gray-900 mb-4">Everything You Need to Succeed</p>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">Powerful tools designed to help you sell more, understand your customers, and grow your business.</p>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="feature-card group">
                    <div class="p-8 border border-gray-200 rounded-xl bg-white">
                        <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mb-6 group-hover:bg-indigo-600 transition-colors duration-300">
                            <svg class="w-6 h-6 text-indigo-600 group-hover:text-white transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3 group-hover:text-indigo-600 transition-colors">Custom Storefront</h3>
                        <p class="text-gray-600 leading-relaxed mb-4">Create a beautiful, branded store that converts visitors into customers. Full customization without coding.</p>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li class="flex items-center">
                                <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Custom domain support
                            </li>
                            <li class="flex items-center">
                                <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Mobile-optimized design
                            </li>
                            <li class="flex items-center">
                                <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                SEO optimization tools
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="feature-card group">
                    <div class="p-8 border border-gray-200 rounded-xl bg-white">
                        <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mb-6 group-hover:bg-indigo-600 transition-colors duration-300">
                            <svg class="w-6 h-6 text-indigo-600 group-hover:text-white transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3 group-hover:text-indigo-600 transition-colors">Secure Delivery</h3>
                        <p class="text-gray-600 leading-relaxed mb-4">Automated, secure delivery of digital products. Built-in protection against unauthorized access.</p>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li class="flex items-center">
                                <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Instant delivery
                            </li>
                            <li class="flex items-center">
                                <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Download protection
                            </li>
                            <li class="flex items-center">
                                <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                License key management
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="feature-card group">
                    <div class="p-8 border border-gray-200 rounded-xl bg-white">
                        <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mb-6 group-hover:bg-indigo-600 transition-colors duration-300">
                            <svg class="w-6 h-6 text-indigo-600 group-hover:text-white transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3 group-hover:text-indigo-600 transition-colors">Analytics & Insights</h3>
                        <p class="text-gray-600 leading-relaxed mb-4">Detailed analytics to help you understand and grow your business. Track everything that matters.</p>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li class="flex items-center">
                                <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Real-time dashboard
                            </li>
                            <li class="flex items-center">
                                <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Customer insights
                            </li>
                            <li class="flex items-center">
                                <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Revenue tracking
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Social Proof Section -->
    <section class="py-20 bg-gray-50">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl mx-auto text-center mb-16">
                <h2 class="text-base text-indigo-600 font-semibold tracking-wide uppercase mb-3">Success Stories</h2>
                <p class="text-4xl font-bold text-gray-900 mb-4">Creators Who Found Success</p>
                <p class="text-xl text-gray-600">Join thousands of creators who are building their digital empire.</p>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-white p-8 rounded-xl shadow-sm hover:shadow-lg transition-shadow">
                    <div class="flex items-center mb-4">
                        <div class="flex-shrink-0">
                            <svg class="h-12 w-12 text-indigo-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 14l9-5-9-5-9 5 9 5z"/>
                                <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900">Sarah K.</h3>
                            <p class="text-gray-600">Digital Course Creator</p>
                        </div>
                    </div>
                    <blockquote class="text-gray-600 leading-relaxed">
                        "I went from $0 to $10K/month in just 6 months selling my design courses. The platform made it incredibly easy to get started."
                    </blockquote>
                    <p class="mt-4 text-sm font-semibold text-indigo-600">$120K+ earned in first year</p>
                </div>
                <div class="bg-white p-8 rounded-xl shadow-sm hover:shadow-lg transition-shadow">
                    <div class="flex items-center mb-4">
                        <div class="flex-shrink-0">
                            <svg class="h-12 w-12 text-indigo-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 14l9-5-9-5-9 5 9 5z"/>
                                <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900">Mark R.</h3>
                            <p class="text-gray-600">Software Developer</p>
                        </div>
                    </div>
                    <blockquote class="text-gray-600 leading-relaxed">
                        "The analytics tools helped me understand my customers better and optimize my pricing. My revenue doubled in 3 months."
                    </blockquote>
                    <p class="mt-4 text-sm font-semibold text-indigo-600">250K+ customers served</p>
                </div>
                <div class="bg-white p-8 rounded-xl shadow-sm hover:shadow-lg transition-shadow">
                    <div class="flex items-center mb-4">
                        <div class="flex-shrink-0">
                            <svg class="h-12 w-12 text-indigo-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 14l9-5-9-5-9 5 9 5z"/>
                                <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900">Lisa M.</h3>
                            <p class="text-gray-600">Content Creator</p>
                        </div>
                    </div>
                    <blockquote class="text-gray-600 leading-relaxed">
                        "The automated delivery system saves me hours every week. I can focus on creating content while the platform handles everything else."
                    </blockquote>
                    <p class="mt-4 text-sm font-semibold text-indigo-600">30+ digital products</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="relative py-20 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-r from-indigo-600 to-violet-600"></div>
        <div class="relative container mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl sm:text-4xl font-bold text-white mb-6">Ready to Start Your Success Story?</h2>
            <p class="text-xl text-indigo-100 mb-8 max-w-2xl mx-auto">Join thousands of creators who are building their digital empire. Start your journey today.</p>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-8 py-4 border border-transparent text-lg font-semibold rounded-lg text-indigo-600 bg-white hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-indigo-600 focus:ring-white transition-all transform hover:scale-105">
                    Create Your Store
                    <svg class="ml-2 -mr-1 w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </a>
                <a href="#features" class="inline-flex items-center justify-center px-8 py-4 border border-white text-lg font-semibold rounded-lg text-white hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-indigo-600 focus:ring-white transition-all">
                    Learn More
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-300">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-white font-semibold text-lg mb-4">Product</h3>
                    <ul class="space-y-3">
                        <li><a href="#features" class="hover:text-white transition-colors">Features</a></li>
                        <li><a href="#pricing" class="hover:text-white transition-colors">Pricing</a></li>
                        <li><a href="#examples" class="hover:text-white transition-colors">Examples</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-white font-semibold text-lg mb-4">Company</h3>
                    <ul class="space-y-3">
                        <li><a href="#about" class="hover:text-white transition-colors">About</a></li>
                        <li><a href="#blog" class="hover:text-white transition-colors">Blog</a></li>
                        <li><a href="#careers" class="hover:text-white transition-colors">Careers</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-white font-semibold text-lg mb-4">Resources</h3>
                    <ul class="space-y-3">
                        <li><a href="#docs" class="hover:text-white transition-colors">Documentation</a></li>
                        <li><a href="#help" class="hover:text-white transition-colors">Help Center</a></li>
                        <li><a href="#guides" class="hover:text-white transition-colors">Guides</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-white font-semibold text-lg mb-4">Legal</h3>
                    <ul class="space-y-3">
                        <li><a href="#privacy" class="hover:text-white transition-colors">Privacy</a></li>
                        <li><a href="#terms" class="hover:text-white transition-colors">Terms</a></li>
                        <li><a href="#security" class="hover:text-white transition-colors">Security</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-12 pt-8 text-center">
                <p class="text-sm">&copy; {{ date('Y') }} {{ config('app.name', 'Digital Marketplace') }}. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
