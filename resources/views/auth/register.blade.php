<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rejestracja - VibeTravels</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-6 text-center">
                VibeTravels
            </h1>
            <h2 class="text-xl font-semibold text-gray-700 mb-6 text-center">
                Rejestracja
            </h2>

            <div class="mb-6 p-4 bg-yellow-100 border border-yellow-400 text-yellow-800 rounded">
                Rejestracja nie jest jeszcze dostępna. Skonfiguruj Laravel Breeze.
            </div>

            <div class="text-center">
                <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-800">
                    ← Wróć do logowania
                </a>
            </div>
        </div>
    </div>
</body>
</html>
