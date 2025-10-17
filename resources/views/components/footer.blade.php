<footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-auto">
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <!-- Company Info -->
            <div class="col-span-1 md:col-span-2">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                    {{ config('app.name', 'VibeTravels') }}
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Zaplanuj swoją wymarzoną podróż z pomocą AI i spersonalizowanych planów.
                </p>
                <div class="flex space-x-4">
                    <a href="https://github.com" target="_blank" rel="noopener noreferrer" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                        <span class="sr-only">GitHub</span>
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Quick Links -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider mb-4">
                    Szybkie linki
                </h4>
                <ul class="space-y-2">
                    @auth
                        <li>
                            <a href="{{ route('dashboard') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                                Panel
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('plans.create') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                                Utwórz plan
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('profile') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                                Profil
                            </a>
                        </li>
                    @else
                        <li>
                            <a href="{{ route('login') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                                Logowanie
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('register') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                                Rejestracja
                            </a>
                        </li>
                    @endauth
                </ul>
            </div>

            <!-- Legal -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider mb-4">
                    Prawne
                </h4>
                <ul class="space-y-2">
                    <li>
                        <a href="#" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                            Polityka prywatności
                        </a>
                    </li>
                    <li>
                        <a href="#" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                            Regulamin
                        </a>
                    </li>
                    <li>
                        <a href="#" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                            Kontakt
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Bottom Bar -->
        <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-700">
            <p class="text-center text-sm text-gray-500 dark:text-gray-400">
                &copy; {{ date('Y') }} {{ config('app.name', 'VibeTravels') }}. Wszelkie prawa zastrzeżone.
            </p>
        </div>
    </div>
</footer>
