<div class="min-h-[calc(100vh-200px)] flex items-center justify-center px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl w-full text-center">
        <!-- Success Icon/Animation -->
        <div class="mb-8">
            <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-green-100">
                <svg class="h-16 w-16 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
        </div>

        <!-- Welcome Message -->
        <h1 class="text-4xl font-bold text-gray-900 mb-4">
            Witaj w VibeTravels, {{ $displayName }}! 🎉
        </h1>

        <p class="text-lg text-gray-600 mb-8">
            Twój profil został pomyślnie skonfigurowany. Możesz teraz zacząć planować swoje wymarzone podróże!
        </p>

        <!-- Feature Highlights -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8 text-left">
            <ul class="space-y-4">
                <li class="flex items-start">
                    <svg class="h-6 w-6 text-blue-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <strong class="text-gray-900">Masz 10 generowań AI miesięcznie</strong>
                        <p class="text-sm text-gray-600">Tworzenie spersonalizowanych planów podróży za pomocą sztucznej inteligencji</p>
                    </div>
                </li>
                <li class="flex items-start">
                    <svg class="h-6 w-6 text-blue-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <strong class="text-gray-900">Twoje preferencje pomogą tworzyć idealne plany</strong>
                        <p class="text-sm text-gray-600">AI uwzględni Twoje zainteresowania, tempo podróży i budżet</p>
                    </div>
                </li>
                <li class="flex items-start">
                    <svg class="h-6 w-6 text-blue-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <strong class="text-gray-900">Eksportuj plany do PDF i zabierz w podróż</strong>
                        <p class="text-sm text-gray-600">Pełny plan offline dostępny w kilka sekund</p>
                    </div>
                </li>
            </ul>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <button
                wire:click="createFirstPlan"
                class="inline-flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
            >
                Stwórz swój pierwszy plan
                <svg class="ml-2 -mr-1 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                </svg>
            </button>

            <button
                wire:click="goToDashboard"
                class="inline-flex items-center justify-center px-8 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
            >
                Przejdź do Dashboard
            </button>
        </div>

        <!-- Auto-dismiss info -->
        <p class="mt-6 text-sm text-gray-500">
            Możesz też przejść do dashboard automatycznie po <span class="font-medium">5 sekundach</span>
        </p>
    </div>
</div>

@push('scripts')
<script>
    // Auto-redirect to dashboard after 5 seconds
    setTimeout(() => {
        @this.goToDashboard();
    }, 5000);
</script>
@endpush
