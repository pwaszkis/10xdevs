<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">
            {{ $editMode ? 'Edytuj plan podróży' : 'Stwórz nowy plan podróży' }}
        </h1>
        <p class="mt-2 text-gray-600">
            {{ $editMode ? 'Zaktualizuj szczegóły swojego planu podróży.' : 'Wypełnij formularz, aby rozpocząć planowanie Twojej podróży.' }}
        </p>
    </div>

    {{-- Limit Info --}}
    @if(!$editMode && isset($limitInfo))
        <div class="mb-6 p-4 rounded-lg {{ $limitInfo['color'] === 'red' ? 'bg-red-50 border border-red-200' : ($limitInfo['color'] === 'yellow' ? 'bg-yellow-50 border border-yellow-200' : 'bg-green-50 border border-green-200') }}">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium {{ $limitInfo['color'] === 'red' ? 'text-red-800' : ($limitInfo['color'] === 'yellow' ? 'text-yellow-800' : 'text-green-800') }}">
                        Generowania AI: {{ $limitInfo['display_text'] }}
                    </p>
                    <p class="text-xs {{ $limitInfo['color'] === 'red' ? 'text-red-600' : ($limitInfo['color'] === 'yellow' ? 'text-yellow-600' : 'text-green-600') }}">
                        Odnowienie: {{ \Carbon\Carbon::parse($limitInfo['reset_date'])->translatedFormat('j F Y') }}
                    </p>
                </div>
                <div class="w-32">
                    <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full {{ $limitInfo['color'] === 'red' ? 'bg-red-500' : ($limitInfo['color'] === 'yellow' ? 'bg-yellow-500' : 'bg-green-500') }}"
                             style="width: {{ $limitInfo['percentage'] }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Success Message --}}
    @if($successMessage)
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-green-800">{{ $successMessage }}</p>
        </div>
    @endif

    {{-- Error Message --}}
    @if($errorMessage)
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-red-800">{{ $errorMessage }}</p>
        </div>
    @endif

    {{-- Form --}}
    <form wire:submit.prevent="generatePlan" class="space-y-6">

        {{-- Title --}}
        <div>
            <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                Tytuł planu <span class="text-red-500">*</span>
            </label>
            <input
                type="text"
                id="title"
                wire:model.blur="title"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="np. Wakacje w Rzymie 2025"
            >
            @error('title')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Destination --}}
        <div>
            <label for="destination" class="block text-sm font-medium text-gray-700 mb-1">
                Destynacja <span class="text-red-500">*</span>
            </label>
            <input
                type="text"
                id="destination"
                wire:model.blur="destination"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="np. Rzym, Włochy"
            >
            @error('destination')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Departure Date & Days --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="departure_date" class="block text-sm font-medium text-gray-700 mb-1">
                    Data wyjazdu <span class="text-red-500">*</span>
                </label>
                <input
                    type="date"
                    id="departure_date"
                    wire:model.live="departure_date"
                    min="{{ now()->addDay()->format('Y-m-d') }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                @error('departure_date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="number_of_days" class="block text-sm font-medium text-gray-700 mb-1">
                    Liczba dni (1-30) <span class="text-red-500">*</span>
                </label>
                <input
                    type="number"
                    id="number_of_days"
                    wire:model.live="number_of_days"
                    min="1"
                    max="30"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                @error('number_of_days')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                @if($endDate)
                    <p class="mt-1 text-sm text-gray-600">
                        Powrót: {{ $endDate->translatedFormat('j F Y') }}
                    </p>
                @endif
            </div>
        </div>

        {{-- Number of People --}}
        <div>
            <label for="number_of_people" class="block text-sm font-medium text-gray-700 mb-1">
                Liczba osób (1-10) <span class="text-red-500">*</span>
            </label>
            <input
                type="number"
                id="number_of_people"
                wire:model.live="number_of_people"
                min="1"
                max="10"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
            @error('number_of_people')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Budget --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                <label for="budget_per_person" class="block text-sm font-medium text-gray-700 mb-1">
                    Budżet na osobę (opcjonalnie)
                </label>
                <input
                    type="number"
                    id="budget_per_person"
                    wire:model.live="budget_per_person"
                    min="0"
                    step="0.01"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="np. 2000"
                >
                @error('budget_per_person')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="budget_currency" class="block text-sm font-medium text-gray-700 mb-1">
                    Waluta
                </label>
                <select
                    id="budget_currency"
                    wire:model.live="budget_currency"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                    @foreach($currencies as $currency)
                        <option value="{{ $currency }}">{{ $currency }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if($totalBudget)
            <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-sm text-blue-800">
                    Całkowity budżet: <span class="font-semibold">{{ number_format($totalBudget, 2) }} {{ $budget_currency }}</span>
                </p>
            </div>
        @endif

        {{-- User Notes --}}
        <div>
            <label for="user_notes" class="block text-sm font-medium text-gray-700 mb-1">
                Pomysły i notatki (opcjonalnie)
            </label>
            <textarea
                id="user_notes"
                wire:model.blur="user_notes"
                rows="4"
                maxlength="5000"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Wpisz swoje pomysły, preferencje lub specjalne wymagania..."
            ></textarea>
            @error('user_notes')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-sm text-gray-500">
                {{ strlen($user_notes ?? '') }}/5000 znaków
            </p>
        </div>

        {{-- Action Buttons --}}
        <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t border-gray-200">
            <button
                type="button"
                wire:click="saveAsDraft"
                wire:loading.attr="disabled"
                class="px-6 py-3 bg-gray-200 text-gray-800 font-medium rounded-lg hover:bg-gray-300 transition disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span wire:loading.remove wire:target="saveAsDraft">
                    💾 Zapisz jako szkic
                </span>
                <span wire:loading wire:target="saveAsDraft">
                    Zapisywanie...
                </span>
            </button>

            <button
                type="submit"
                wire:loading.attr="disabled"
                @if(!$canGenerate) disabled @endif
                class="px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span wire:loading.remove wire:target="generatePlan">
                    🤖 Generuj plan AI
                </span>
                <span wire:loading wire:target="generatePlan">
                    Generowanie...
                </span>
            </button>

            <a
                href="{{ route('dashboard') }}"
                class="px-6 py-3 text-center bg-white text-gray-700 font-medium rounded-lg border border-gray-300 hover:bg-gray-50 transition"
            >
                Anuluj
            </a>
        </div>

        @if(!$canGenerate)
            <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <p class="text-sm text-yellow-800">
                    ⚠️ Osiągnięto limit generowań. Możesz zapisać plan jako szkic i wygenerować go później.
                </p>
            </div>
        @endif
    </form>
</div>
