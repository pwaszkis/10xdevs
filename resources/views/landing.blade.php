<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name') }} - Zaplanuj idealną podróż z AI</title>
        <meta name="description" content="Twórz spersonalizowane plany podróży w sekundy dzięki AI. Oszczędzaj czas, odkrywaj ukryte perły i podróżuj mądrzej.">

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
                                Zaloguj się
                            </a>
                            <a href="{{ route('register') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm">
                                Rozpocznij
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
                            Zaplanuj idealną podróż
                            <span class="block text-blue-600 dark:text-blue-400">z AI w sekundy</span>
                        </h1>
                        <p class="mt-6 max-w-2xl mx-auto text-xl text-gray-600 dark:text-gray-300">
                            Przestań tracić godziny na researchu. Pozwól AI stworzyć spersonalizowany plan dzień po dniu,
                            dostosowany do Twoich preferencji, budżetu i stylu podróżowania.
                        </p>
                        <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
                            <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-8 py-4 border border-transparent text-base font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 transition shadow-lg">
                                🚀 Zacznij za darmo
                            </a>
                            <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-8 py-4 border border-gray-300 dark:border-gray-600 text-base font-medium rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                Zaloguj się
                            </a>
                        </div>
                        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                            ✨ 10 bezpłatnych planów AI miesięcznie • Bez karty kredytowej
                        </p>
                    </div>
                </div>

                {{-- Features Section --}}
                <div class="bg-white dark:bg-gray-800 py-16 sm:py-24">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div class="text-center mb-16">
                            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-white mb-4">
                                Dlaczego {{ config('app.name') }}?
                            </h2>
                            <p class="text-lg text-gray-600 dark:text-gray-300">
                                Planowanie podróży proste, inteligentne i spersonalizowane
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                            {{-- Feature 1 --}}
                            <div class="text-center p-6 rounded-lg bg-blue-50 dark:bg-gray-700">
                                <div class="w-16 h-16 mx-auto mb-4 bg-blue-600 rounded-full flex items-center justify-center text-3xl">
                                    🤖
                                </div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                                    Planowanie z AI
                                </h3>
                                <p class="text-gray-600 dark:text-gray-300">
                                    Nasze zaawansowane AI analizuje Twoje preferencje, tworząc idealne plany dzień po dniu
                                    z atrakcjami, restauracjami i aktywnościami.
                                </p>
                            </div>

                            {{-- Feature 2 --}}
                            <div class="text-center p-6 rounded-lg bg-green-50 dark:bg-gray-700">
                                <div class="w-16 h-16 mx-auto mb-4 bg-green-600 rounded-full flex items-center justify-center text-3xl">
                                    ⚡
                                </div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                                    Oszczędność czasu
                                </h3>
                                <p class="text-gray-600 dark:text-gray-300">
                                    Uzyskaj kompletny plan podróży w 30-60 sekund zamiast spędzać dni
                                    na researchu destynacji, restauracji i atrakcji.
                                </p>
                            </div>

                            {{-- Feature 3 --}}
                            <div class="text-center p-6 rounded-lg bg-purple-50 dark:bg-gray-700">
                                <div class="w-16 h-16 mx-auto mb-4 bg-purple-600 rounded-full flex items-center justify-center text-3xl">
                                    🎯
                                </div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                                    Spersonalizowane plany
                                </h3>
                                <p class="text-gray-600 dark:text-gray-300">
                                    Dostosowane do Twojego tempa podróży, budżetu, zainteresowań i preferencji żywieniowych.
                                    Każdy plan jest unikalny.
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
                                        Plany dzień po dniu
                                    </h4>
                                    <p class="text-gray-600 dark:text-gray-300">
                                        Zorganizowane harmonogramy z czasem, lokalizacjami i szczegółowymi opisami
                                        każdej aktywności.
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start space-x-4">
                                <div class="flex-shrink-0 w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center text-2xl">
                                    💰
                                </div>
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                                        Kontrola budżetu
                                    </h4>
                                    <p class="text-gray-600 dark:text-gray-300">
                                        Ustaw budżet na osobę i otrzymuj rekomendacje dopasowane do Twoich
                                        możliwości finansowych.
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start space-x-4">
                                <div class="flex-shrink-0 w-12 h-12 bg-orange-100 dark:bg-orange-900 rounded-lg flex items-center justify-center text-2xl">
                                    📱
                                </div>
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                                        Eksport do PDF
                                    </h4>
                                    <p class="text-gray-600 dark:text-gray-300">
                                        Pobierz plan jako pięknie sformatowany PDF, aby mieć go offline
                                        podczas podróży.
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start space-x-4">
                                <div class="flex-shrink-0 w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center text-2xl">
                                    🔄
                                </div>
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                                        Regeneruj i udoskonalaj
                                    </h4>
                                    <p class="text-gray-600 dark:text-gray-300">
                                        Nie podoba Ci się plan? Wygeneruj go ponownie natychmiast lub zapisz szkic
                                        do późniejszej edycji.
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
                                Jak to działa
                            </h2>
                            <p class="text-lg text-gray-600 dark:text-gray-300">
                                Od pomysłu do planu w 3 prostych krokach
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                            <div class="text-center">
                                <div class="w-16 h-16 mx-auto mb-4 bg-blue-600 rounded-full flex items-center justify-center text-white text-2xl font-bold">
                                    1
                                </div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                                    Podaj swoje preferencje
                                </h3>
                                <p class="text-gray-600 dark:text-gray-300">
                                    Podziel się destynacją, datami, budżetem, zainteresowaniami i stylem podróżowania
                                    w szybkim procesie onboardingu.
                                </p>
                            </div>

                            <div class="text-center">
                                <div class="w-16 h-16 mx-auto mb-4 bg-blue-600 rounded-full flex items-center justify-center text-white text-2xl font-bold">
                                    2
                                </div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                                    AI tworzy Twój plan
                                </h3>
                                <p class="text-gray-600 dark:text-gray-300">
                                    Nasze AI analizuje tysiące opcji, tworząc spersonalizowany plan
                                    z aktywnościami, jedzeniem i logistyką.
                                </p>
                            </div>

                            <div class="text-center">
                                <div class="w-16 h-16 mx-auto mb-4 bg-blue-600 rounded-full flex items-center justify-center text-white text-2xl font-bold">
                                    3
                                </div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                                    Przeglądaj i podróżuj
                                </h3>
                                <p class="text-gray-600 dark:text-gray-300">
                                    Przejrzyj szczegółowy plan dzień po dniu, pobierz go jako PDF i ciesz się
                                    idealnie zorganizowaną podróżą.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- CTA Section --}}
                <div class="bg-blue-600 dark:bg-blue-700 py-16">
                    <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
                        <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4">
                            Gotowy zaplanować kolejną przygodę?
                        </h2>
                        <p class="text-xl text-blue-100 mb-8">
                            Dołącz do tysięcy podróżników, którzy oszczędzają czas i odkrywają więcej dzięki AI.
                        </p>
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-8 py-4 border-2 border-white text-base font-medium rounded-lg text-blue-600 bg-white hover:bg-blue-50 transition shadow-lg">
                            🚀 Zacznij planować za darmo
                        </a>
                        <p class="mt-4 text-sm text-blue-100">
                            Bez karty kredytowej • 10 bezpłatnych planów AI miesięcznie
                        </p>
                    </div>
                </div>
            </main>

            {{-- Footer --}}
            <x-footer />
        </div>
    </body>
</html>
