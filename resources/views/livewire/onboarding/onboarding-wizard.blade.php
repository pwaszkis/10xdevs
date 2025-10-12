<div class="bg-white rounded-lg shadow-lg p-6 sm:p-8">
    <!-- Progress Bar -->
    <x-onboarding.progress-bar :current-step="$currentStep" :total-steps="4" />

    <!-- Step Content -->
    <div class="mt-8">
        @if ($currentStep === 1)
            <!-- Step 1: Basic Information -->
            <div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">
                    Witaj! Zacznijmy od podstaw
                </h2>
                <p class="text-gray-600 mb-6">
                    Powiedz nam trochę o sobie, abyśmy mogli spersonalizować Twoje doświadczenie.
                </p>

                <div class="space-y-6">
                    <!-- Nickname -->
                    <div>
                        <label for="nickname" class="block text-sm font-medium text-gray-700 mb-2">
                            Jak mamy Cię nazywać? <span class="text-red-600">*</span>
                        </label>
                        <input
                            type="text"
                            id="nickname"
                            wire:model.blur="nickname"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Np. Ania, Bartek..."
                            maxlength="50"
                            required
                            aria-required="true"
                            aria-describedby="nickname-error"
                        >
                        @error('nickname')
                            <p class="mt-1 text-sm text-red-600" id="nickname-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Home Location -->
                    <div>
                        <label for="homeLocation" class="block text-sm font-medium text-gray-700 mb-2">
                            Skąd pochodzisz? <span class="text-red-600">*</span>
                        </label>
                        <input
                            type="text"
                            id="homeLocation"
                            wire:model.blur="homeLocation"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Np. Warszawa, Polska"
                            maxlength="100"
                            required
                            aria-required="true"
                            aria-describedby="homeLocation-error"
                        >
                        @error('homeLocation')
                            <p class="mt-1 text-sm text-red-600" id="homeLocation-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        @elseif ($currentStep === 2)
            <!-- Step 2: Interest Categories -->
            <div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">
                    Co Cię interesuje podczas podróży?
                </h2>
                <p class="text-gray-600 mb-6">
                    Wybierz przynajmniej jedną kategorię. Pomoże nam to tworzyć lepsze plany podróży dla Ciebie.
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach ($this->availableInterests as $key => $label)
                        <button
                            type="button"
                            wire:click="toggleInterest('{{ $key }}')"
                            class="flex items-center p-4 border-2 rounded-lg transition-all duration-200 text-left
                                {{ $this->isInterestSelected($key)
                                    ? 'border-blue-600 bg-blue-50 ring-2 ring-blue-600'
                                    : 'border-gray-300 hover:border-blue-400 hover:bg-gray-50' }}"
                            aria-pressed="{{ $this->isInterestSelected($key) ? 'true' : 'false' }}"
                        >
                            <input
                                type="checkbox"
                                checked="{{ $this->isInterestSelected($key) }}"
                                class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500 pointer-events-none"
                                tabindex="-1"
                                aria-hidden="true"
                            >
                            <span class="ml-3 text-sm font-medium text-gray-900">
                                {{ $label }}
                            </span>
                        </button>
                    @endforeach
                </div>

                @error('interestCategories')
                    <p class="mt-4 text-sm text-red-600" role="alert">{{ $message }}</p>
                @enderror
            </div>
        @elseif ($currentStep === 3)
            <!-- Step 3: Practical Parameters -->
            <div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">
                    Jak planujesz podróżować?
                </h2>
                <p class="text-gray-600 mb-6">
                    Pomóż nam zrozumieć Twój styl podróżowania.
                </p>

                <div class="space-y-8">
                    <!-- Travel Pace -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            Tempo podróży <span class="text-red-600">*</span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            @foreach (['spokojne' => 'Spokojne', 'umiarkowane' => 'Umiarkowane', 'intensywne' => 'Intensywne'] as $value => $label)
                                <button
                                    type="button"
                                    wire:click="$set('travelPace', '{{ $value }}')"
                                    class="p-4 border-2 rounded-lg transition-all duration-200 text-center
                                        {{ $travelPace === $value
                                            ? 'border-blue-600 bg-blue-50 ring-2 ring-blue-600'
                                            : 'border-gray-300 hover:border-blue-400 hover:bg-gray-50' }}"
                                    role="radio"
                                    aria-checked="{{ $travelPace === $value ? 'true' : 'false' }}"
                                >
                                    <span class="text-sm font-medium text-gray-900">{{ $label }}</span>
                                </button>
                            @endforeach
                        </div>
                        @error('travelPace')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Budget Level -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            Budżet <span class="text-red-600">*</span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            @foreach (['ekonomiczny' => 'Ekonomiczny', 'standardowy' => 'Standardowy', 'premium' => 'Premium'] as $value => $label)
                                <button
                                    type="button"
                                    wire:click="$set('budgetLevel', '{{ $value }}')"
                                    class="p-4 border-2 rounded-lg transition-all duration-200 text-center
                                        {{ $budgetLevel === $value
                                            ? 'border-blue-600 bg-blue-50 ring-2 ring-blue-600'
                                            : 'border-gray-300 hover:border-blue-400 hover:bg-gray-50' }}"
                                    role="radio"
                                    aria-checked="{{ $budgetLevel === $value ? 'true' : 'false' }}"
                                >
                                    <span class="text-sm font-medium text-gray-900">{{ $label }}</span>
                                </button>
                            @endforeach
                        </div>
                        @error('budgetLevel')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Transport Preference -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            Transport <span class="text-red-600">*</span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            @foreach (['pieszo_publiczny' => 'Pieszo i publiczny', 'wynajem_auta' => 'Wynajem auta', 'mix' => 'Mix'] as $value => $label)
                                <button
                                    type="button"
                                    wire:click="$set('transportPreference', '{{ $value }}')"
                                    class="p-4 border-2 rounded-lg transition-all duration-200 text-center
                                        {{ $transportPreference === $value
                                            ? 'border-blue-600 bg-blue-50 ring-2 ring-blue-600'
                                            : 'border-gray-300 hover:border-blue-400 hover:bg-gray-50' }}"
                                    role="radio"
                                    aria-checked="{{ $transportPreference === $value ? 'true' : 'false' }}"
                                >
                                    <span class="text-sm font-medium text-gray-900">{{ $label }}</span>
                                </button>
                            @endforeach
                        </div>
                        @error('transportPreference')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Restrictions -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            Ograniczenia <span class="text-red-600">*</span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            @foreach (['brak' => 'Brak', 'dieta' => 'Dieta', 'mobilnosc' => 'Mobilność'] as $value => $label)
                                <button
                                    type="button"
                                    wire:click="$set('restrictions', '{{ $value }}')"
                                    class="p-4 border-2 rounded-lg transition-all duration-200 text-center
                                        {{ $restrictions === $value
                                            ? 'border-blue-600 bg-blue-50 ring-2 ring-blue-600'
                                            : 'border-gray-300 hover:border-blue-400 hover:bg-gray-50' }}"
                                    role="radio"
                                    aria-checked="{{ $restrictions === $value ? 'true' : 'false' }}"
                                >
                                    <span class="text-sm font-medium text-gray-900">{{ $label }}</span>
                                </button>
                            @endforeach
                        </div>
                        @error('restrictions')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        @elseif ($currentStep === 4)
            <!-- Step 4: Summary -->
            <div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">
                    Świetnie! Podsumujmy Twoje preferencje
                </h2>
                <p class="text-gray-600 mb-6">
                    Sprawdź czy wszystko jest prawidłowe. Zawsze możesz zmienić te ustawienia później.
                </p>

                <div class="space-y-6">
                    <!-- Basic Info -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-900 mb-2">Podstawowe informacje</h3>
                        <p class="text-sm text-gray-700"><strong>Nickname:</strong> {{ $nickname }}</p>
                        <p class="text-sm text-gray-700"><strong>Lokalizacja:</strong> {{ $homeLocation }}</p>
                    </div>

                    <!-- Interests -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-900 mb-2">Zainteresowania</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($interestCategories as $category)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                    {{ $this->availableInterests[$category] ?? $category }}
                                </span>
                            @endforeach
                        </div>
                    </div>

                    <!-- Practical Parameters -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-900 mb-2">Parametry podróży</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm text-gray-700">
                            <p><strong>Tempo:</strong> {{ ucfirst($travelPace) }}</p>
                            <p><strong>Budżet:</strong> {{ ucfirst($budgetLevel) }}</p>
                            <p><strong>Transport:</strong> {{ str_replace('_', ' ', $transportPreference) }}</p>
                            <p><strong>Ograniczenia:</strong> {{ ucfirst($restrictions) }}</p>
                        </div>
                    </div>
                </div>

                @if (session('error'))
                    <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-md">
                        <p class="text-sm text-red-800">{{ session('error') }}</p>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <!-- Navigation Buttons -->
    <div class="mt-8 flex justify-between items-center">
        @if ($currentStep > 1)
            <button
                type="button"
                wire:click="previousStep"
                class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
            >
                Wstecz
            </button>
        @else
            <div></div>
        @endif

        @if ($currentStep < 4)
            <button
                type="button"
                wire:click="nextStep"
                class="px-6 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                @if(!$this->canProceed) disabled @endif
            >
                Dalej
            </button>
        @else
            <button
                type="button"
                wire:click="completeOnboarding"
                class="px-6 py-3 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="completeOnboarding">Zakończ</span>
                <span wire:loading wire:target="completeOnboarding">Zapisywanie...</span>
            </button>
        @endif
    </div>
</div>
