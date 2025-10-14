<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            Preferencje podróżnicze
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Zaktualizuj swoje preferencje, aby dostosować plany podróży do swoich potrzeb.
        </p>
    </header>

    <form wire:submit="updatePreferences" class="mt-6 space-y-8">
        <!-- Interest Categories -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                Kategorie zainteresowań <span class="text-red-600">*</span>
            </label>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                Wybierz przynajmniej jedną kategorię. Pomoże to w tworzeniu lepszych planów podróży.
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach ($availableInterests as $key => $label)
                    <button
                        type="button"
                        wire:click="toggleInterest('{{ $key }}')"
                        class="flex items-center p-3 border-2 rounded-lg transition-all duration-200 text-left
                            {{ $this->isInterestSelected($key)
                                ? 'border-blue-600 bg-blue-50 dark:bg-blue-900/20 ring-2 ring-blue-600'
                                : 'border-gray-300 dark:border-gray-600 hover:border-blue-400 hover:bg-gray-50 dark:hover:bg-gray-700' }}"
                        aria-pressed="{{ $this->isInterestSelected($key) ? 'true' : 'false' }}"
                    >
                        <input
                            type="checkbox"
                            @checked($this->isInterestSelected($key))
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 pointer-events-none"
                            tabindex="-1"
                            aria-hidden="true"
                        >
                        <span class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ $label }}
                        </span>
                    </button>
                @endforeach
            </div>

            @error('interestCategories')
                <p class="mt-2 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <!-- Travel Pace -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                Tempo podróży <span class="text-red-600">*</span>
            </label>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                @foreach (['spokojne' => 'Spokojne', 'umiarkowane' => 'Umiarkowane', 'intensywne' => 'Intensywne'] as $value => $label)
                    <button
                        type="button"
                        wire:click="setTravelPace('{{ $value }}')"
                        class="p-3 border-2 rounded-lg transition-all duration-200 text-center
                            {{ $travelPace === $value
                                ? 'border-blue-600 bg-blue-50 dark:bg-blue-900/20 ring-2 ring-blue-600'
                                : 'border-gray-300 dark:border-gray-600 hover:border-blue-400 hover:bg-gray-50 dark:hover:bg-gray-700' }}"
                        role="radio"
                        aria-checked="{{ $travelPace === $value ? 'true' : 'false' }}"
                    >
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $label }}</span>
                    </button>
                @endforeach
            </div>
            @error('travelPace')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Budget Level -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                Budżet <span class="text-red-600">*</span>
            </label>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                @foreach (['ekonomiczny' => 'Ekonomiczny', 'standardowy' => 'Standardowy', 'premium' => 'Premium'] as $value => $label)
                    <button
                        type="button"
                        wire:click="setBudgetLevel('{{ $value }}')"
                        class="p-3 border-2 rounded-lg transition-all duration-200 text-center
                            {{ $budgetLevel === $value
                                ? 'border-blue-600 bg-blue-50 dark:bg-blue-900/20 ring-2 ring-blue-600'
                                : 'border-gray-300 dark:border-gray-600 hover:border-blue-400 hover:bg-gray-50 dark:hover:bg-gray-700' }}"
                        role="radio"
                        aria-checked="{{ $budgetLevel === $value ? 'true' : 'false' }}"
                    >
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $label }}</span>
                    </button>
                @endforeach
            </div>
            @error('budgetLevel')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Transport Preference -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                Transport <span class="text-red-600">*</span>
            </label>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                @foreach (['pieszo_publiczny' => 'Pieszo i publiczny', 'wynajem_auta' => 'Wynajem auta', 'mix' => 'Mix'] as $value => $label)
                    <button
                        type="button"
                        wire:click="setTransportPreference('{{ $value }}')"
                        class="p-3 border-2 rounded-lg transition-all duration-200 text-center
                            {{ $transportPreference === $value
                                ? 'border-blue-600 bg-blue-50 dark:bg-blue-900/20 ring-2 ring-blue-600'
                                : 'border-gray-300 dark:border-gray-600 hover:border-blue-400 hover:bg-gray-50 dark:hover:bg-gray-700' }}"
                        role="radio"
                        aria-checked="{{ $transportPreference === $value ? 'true' : 'false' }}"
                    >
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $label }}</span>
                    </button>
                @endforeach
            </div>
            @error('transportPreference')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Restrictions -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                Ograniczenia <span class="text-red-600">*</span>
            </label>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                @foreach (['brak' => 'Brak', 'dieta' => 'Dieta', 'mobilnosc' => 'Mobilność'] as $value => $label)
                    <button
                        type="button"
                        wire:click="setRestrictions('{{ $value }}')"
                        class="p-3 border-2 rounded-lg transition-all duration-200 text-center
                            {{ $restrictions === $value
                                ? 'border-blue-600 bg-blue-50 dark:bg-blue-900/20 ring-2 ring-blue-600'
                                : 'border-gray-300 dark:border-gray-600 hover:border-blue-400 hover:bg-gray-50 dark:hover:bg-gray-700' }}"
                        role="radio"
                        aria-checked="{{ $restrictions === $value ? 'true' : 'false' }}"
                    >
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $label }}</span>
                    </button>
                @endforeach
            </div>
            @error('restrictions')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Submit Button -->
        <div class="flex items-center gap-4">
            <button
                type="submit"
                class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="updatePreferences">Zapisz preferencje</span>
                <span wire:loading wire:target="updatePreferences">Zapisywanie...</span>
            </button>

            @if (session('status') === 'preferences-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 3000)"
                    class="text-sm text-gray-600 dark:text-gray-400"
                >
                    Preferencje zostały zapisane.
                </p>
            @endif

            @if (session('error'))
                <p class="text-sm text-red-600 dark:text-red-400">
                    {{ session('error') }}
                </p>
            @endif
        </div>
    </form>
</section>
