<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - VibeTravels</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h1 class="text-3xl font-bold text-gray-900 mb-4">
                Dashboard
            </h1>
            <p class="text-gray-600 mb-4">
                Witaj w VibeTravels! Dashboard będzie dostępny wkrótce.
            </p>
            <div class="space-y-2">
                <p class="text-sm text-gray-500">
                    Aby przetestować tworzenie planu podróży, przejdź do:
                </p>
                <ul class="list-disc list-inside text-sm text-gray-500 ml-4 space-y-1">
                    <li>
                        <a href="{{ route('plans.create') }}" class="text-blue-600 hover:text-blue-800 underline">
                            Stwórz nowy plan
                        </a>
                    </li>
                    <li>
                        <a href="/dev/plans/create" class="text-blue-600 hover:text-blue-800 underline">
                            Stwórz nowy plan (dev - bez logowania)
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
