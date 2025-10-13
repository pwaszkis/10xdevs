<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name') }} - Plan Your Perfect Trip with AI</title>
        <meta name="description" content="Create personalized travel itineraries in seconds with AI-powered planning. Save time, discover hidden gems, and travel smarter.">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gradient-to-b from-blue-50 to-white dark:from-gray-900 dark:to-gray-800 flex flex-col">
            {{-- Navigation --}}
            <nav class="bg-white dark:bg-gray-800 shadow-sm">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex items-center">
                            <a href="{{ route('home') }}" class="flex items-center">
                                <x-application-logo class="block h-9 w-auto fill-current text-blue-600 dark:text-blue-400" />
                                <span class="ml-3 text-xl font-bold text-gray-900 dark:text-white">{{ config('app.name') }}</span>
                            </a>
                        </div>
                        <div class="flex items-center space-x-4">
                            <a href="{{ route('login') }}" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 text-sm font-medium transition">
                                Log In
                            </a>
                            <a href="{{ route('register') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm">
                                Get Started
                            </a>
                        </div>
                    </div>
                </div>
            </nav>

            {{-- Hero Section --}}
            <main class="flex-1">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24">
                    <div class="text-center">
                        <h1 class="text-4xl sm:text-5xl md:text-6xl font-extrabold text-gray-900 dark:text-white mb-6">
                            Plan Your Perfect Trip
                            <span class="block text-blue-600 dark:text-blue-400">with AI in Seconds</span>
                        </h1>
                        <p class="mt-6 max-w-2xl mx-auto text-xl text-gray-600 dark:text-gray-300">
                            Stop spending hours researching. Let AI create a personalized day-by-day itinerary
                            tailored to your preferences, budget, and travel style.
                        </p>
                        <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
                            <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-8 py-4 border border-transparent text-base font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 transition shadow-lg">
                                🚀 Start Planning Free
                            </a>
                            <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-8 py-4 border border-gray-300 dark:border-gray-600 text-base font-medium rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                Sign In
                            </a>
                        </div>
                        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                            ✨ 10 free AI-generated plans per month • No credit card required
                        </p>
                    </div>
                </div>

                {{-- Features Section --}}
                <div class="bg-white dark:bg-gray-800 py-16 sm:py-24">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div class="text-center mb-16">
                            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-white mb-4">
                                Why Choose {{ config('app.name') }}?
                            </h2>
                            <p class="text-lg text-gray-600 dark:text-gray-300">
                                Travel planning made simple, smart, and personalized
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                            {{-- Feature 1 --}}
                            <div class="text-center p-6 rounded-lg bg-blue-50 dark:bg-gray-700">
                                <div class="w-16 h-16 mx-auto mb-4 bg-blue-600 rounded-full flex items-center justify-center text-3xl">
                                    🤖
                                </div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                                    AI-Powered Planning
                                </h3>
                                <p class="text-gray-600 dark:text-gray-300">
                                    Our advanced AI analyzes your preferences to create perfect day-by-day itineraries
                                    with attractions, restaurants, and activities.
                                </p>
                            </div>

                            {{-- Feature 2 --}}
                            <div class="text-center p-6 rounded-lg bg-green-50 dark:bg-gray-700">
                                <div class="w-16 h-16 mx-auto mb-4 bg-green-600 rounded-full flex items-center justify-center text-3xl">
                                    ⚡
                                </div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                                    Save Hours of Time
                                </h3>
                                <p class="text-gray-600 dark:text-gray-300">
                                    Get a complete travel plan in 30-60 seconds instead of spending days
                                    researching destinations, restaurants, and activities.
                                </p>
                            </div>

                            {{-- Feature 3 --}}
                            <div class="text-center p-6 rounded-lg bg-purple-50 dark:bg-gray-700">
                                <div class="w-16 h-16 mx-auto mb-4 bg-purple-600 rounded-full flex items-center justify-center text-3xl">
                                    🎯
                                </div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                                    Personalized Experience
                                </h3>
                                <p class="text-gray-600 dark:text-gray-300">
                                    Tailored to your travel pace, budget, interests, and dietary restrictions.
                                    Every plan is unique to you.
                                </p>
                            </div>
                        </div>

                        {{-- Additional Features --}}
                        <div class="mt-16 grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="flex items-start space-x-4">
                                <div class="flex-shrink-0 w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center text-2xl">
                                    📅
                                </div>
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                                        Day-by-Day Itineraries
                                    </h4>
                                    <p class="text-gray-600 dark:text-gray-300">
                                        Organized schedules with timing, locations, and detailed descriptions
                                        for each activity.
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start space-x-4">
                                <div class="flex-shrink-0 w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center text-2xl">
                                    💰
                                </div>
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                                        Budget Tracking
                                    </h4>
                                    <p class="text-gray-600 dark:text-gray-300">
                                        Set your budget per person and get recommendations that fit your
                                        financial constraints.
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start space-x-4">
                                <div class="flex-shrink-0 w-12 h-12 bg-orange-100 dark:bg-orange-900 rounded-lg flex items-center justify-center text-2xl">
                                    📱
                                </div>
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                                        Export to PDF
                                    </h4>
                                    <p class="text-gray-600 dark:text-gray-300">
                                        Download your itinerary as a beautifully formatted PDF to take offline
                                        during your trip.
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start space-x-4">
                                <div class="flex-shrink-0 w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center text-2xl">
                                    🔄
                                </div>
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                                        Regenerate & Refine
                                    </h4>
                                    <p class="text-gray-600 dark:text-gray-300">
                                        Not happy with the plan? Regenerate it instantly or save drafts to
                                        work on later.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- How It Works --}}
                <div class="py-16 sm:py-24">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div class="text-center mb-16">
                            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-white mb-4">
                                How It Works
                            </h2>
                            <p class="text-lg text-gray-600 dark:text-gray-300">
                                From idea to itinerary in 3 simple steps
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                            <div class="text-center">
                                <div class="w-16 h-16 mx-auto mb-4 bg-blue-600 rounded-full flex items-center justify-center text-white text-2xl font-bold">
                                    1
                                </div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                                    Tell Us Your Preferences
                                </h3>
                                <p class="text-gray-600 dark:text-gray-300">
                                    Share your destination, dates, budget, interests, and travel style in a
                                    quick onboarding process.
                                </p>
                            </div>

                            <div class="text-center">
                                <div class="w-16 h-16 mx-auto mb-4 bg-blue-600 rounded-full flex items-center justify-center text-white text-2xl font-bold">
                                    2
                                </div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                                    AI Creates Your Plan
                                </h3>
                                <p class="text-gray-600 dark:text-gray-300">
                                    Our AI analyzes thousands of options to build a personalized itinerary
                                    with activities, dining, and logistics.
                                </p>
                            </div>

                            <div class="text-center">
                                <div class="w-16 h-16 mx-auto mb-4 bg-blue-600 rounded-full flex items-center justify-center text-white text-2xl font-bold">
                                    3
                                </div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                                    Review & Travel
                                </h3>
                                <p class="text-gray-600 dark:text-gray-300">
                                    Review your detailed day-by-day plan, download it as PDF, and enjoy
                                    your perfectly organized trip.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- CTA Section --}}
                <div class="bg-blue-600 dark:bg-blue-700 py-16">
                    <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
                        <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4">
                            Ready to Plan Your Next Adventure?
                        </h2>
                        <p class="text-xl text-blue-100 mb-8">
                            Join thousands of travelers who save time and discover more with AI-powered planning.
                        </p>
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-8 py-4 border-2 border-white text-base font-medium rounded-lg text-blue-600 bg-white hover:bg-blue-50 transition shadow-lg">
                            🚀 Start Planning for Free
                        </a>
                        <p class="mt-4 text-sm text-blue-100">
                            No credit card required • 10 free AI plans per month
                        </p>
                    </div>
                </div>
            </main>

            {{-- Footer --}}
            <x-footer />
        </div>
    </body>
</html>
