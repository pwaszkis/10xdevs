<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logowanie - VibeTravels</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-6 text-center">
                VibeTravels
            </h1>
            <h2 class="text-xl font-semibold text-gray-700 mb-6 text-center">
                Zaloguj się
            </h2>

            @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Email
                    </label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        required
                        autofocus
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        value="{{ old('email') }}"
                    >
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        Hasło
                    </label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="remember" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                        <span class="ml-2 text-sm text-gray-600">Zapamiętaj mnie</span>
                    </label>
                </div>

                <button
                    type="submit"
                    class="w-full px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition"
                >
                    Zaloguj się
                </button>

                <div class="mt-4 text-center text-sm">
                    <a href="{{ route('password.request') }}" class="text-blue-600 hover:text-blue-800">
                        Zapomniałeś hasła?
                    </a>
                </div>

                <div class="mt-2 text-center text-sm text-gray-600">
                    Nie masz konta?
                    <a href="{{ route('register') }}" class="text-blue-600 hover:text-blue-800">
                        Zarejestruj się
                    </a>
                </div>
            </form>

            <div class="mt-8 pt-6 border-t border-gray-200">
                <p class="text-xs text-gray-500 text-center">
                    <strong>Uwaga:</strong> To jest placeholder. Aby w pełni skonfigurować autentykację, uruchom:<br>
                    <code class="bg-gray-100 px-2 py-1 rounded">php artisan breeze:install</code>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
