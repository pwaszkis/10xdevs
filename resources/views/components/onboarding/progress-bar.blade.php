@props(['currentStep' => 1, 'totalSteps' => 4])

@php
    $percentage = ($currentStep / $totalSteps) * 100;
@endphp

<div class="mb-8" role="progressbar" aria-valuenow="{{ $currentStep }}" aria-valuemin="1" aria-valuemax="{{ $totalSteps }}" aria-label="Postęp onboardingu">
    <!-- Step Counter -->
    <div class="flex justify-between items-center mb-2">
        <span class="text-sm font-medium text-gray-700">
            Krok {{ $currentStep }} z {{ $totalSteps }}
        </span>
        <span class="text-sm font-medium text-gray-700">
            {{ round($percentage) }}%
        </span>
    </div>

    <!-- Progress Bar -->
    <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
        <div
            class="bg-blue-600 h-2.5 rounded-full transition-all duration-500 ease-in-out"
            style="width: {{ $percentage }}%"
        ></div>
    </div>

    <!-- Step Labels (optional, shown on larger screens) -->
    <div class="hidden sm:flex justify-between mt-4 text-xs text-gray-500">
        <span class="{{ $currentStep >= 1 ? 'text-blue-600 font-medium' : '' }}">
            Dane podstawowe
        </span>
        <span class="{{ $currentStep >= 2 ? 'text-blue-600 font-medium' : '' }}">
            Zainteresowania
        </span>
        <span class="{{ $currentStep >= 3 ? 'text-blue-600 font-medium' : '' }}">
            Parametry
        </span>
        <span class="{{ $currentStep >= 4 ? 'text-blue-600 font-medium' : '' }}">
            Zakończenie
        </span>
    </div>
</div>
