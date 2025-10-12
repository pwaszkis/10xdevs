<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'VibeTravels') }} - Onboarding</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Styles -->
    @livewireStyles
</head>
<body class="font-sans antialiased bg-gray-50">
    <!-- Skip to main content link for accessibility -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-blue-600 focus:text-white focus:rounded-md">
        Przejdź do głównej treści
    </a>

    <div class="min-h-screen flex flex-col">
        <!-- Simple Header (optional logo) -->
        <header class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-center">
                    <a href="/" class="text-2xl font-bold text-blue-600">
                        VibeTravels
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main id="main-content" class="flex-1 flex items-center justify-center py-8 px-4 sm:px-6 lg:px-8">
            <div class="w-full max-w-2xl">
                {{ $slot }}
            </div>
        </main>

        <!-- Footer (minimal) -->
        <footer class="py-6 text-center text-sm text-gray-500">
            <p>&copy; {{ date('Y') }} VibeTravels. Wszystkie prawa zastrzeżone.</p>
        </footer>
    </div>

    @livewireScripts
</body>
</html>
