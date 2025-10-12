@props(['point'])

@php
    use App\DataTransferObjects\PlanPointViewModel;
    $pointViewModel = PlanPointViewModel::fromArray($point);

    $dayPartIcon = match ($pointViewModel->dayPart) {
        'rano' => '🌅',
        'poludnie' => '☀️',
        'popołudnie' => '🌇',
        'wieczór' => '🌙',
        default => '📍',
    };

    $formattedDuration = $pointViewModel->getFormattedDuration();
@endphp

<div
    class="plan-point-card bg-white rounded-lg shadow-sm p-4 cursor-pointer hover:shadow-md transition"
    x-data="{ expanded: false }"
    @click="expanded = !expanded"
    :class="{ 'shadow-md': expanded }"
>
    {{-- Collapsed State (always visible) --}}
    <div class="point-collapsed flex items-center justify-between">
        <div class="flex items-center gap-3 flex-1">
            <span class="day-part-icon text-2xl">{{ $dayPartIcon }}</span>
            <h4 class="text-base font-medium text-gray-900">{{ $pointViewModel->name }}</h4>
        </div>
        <span class="duration text-sm text-gray-500 ml-2">
            {{ $formattedDuration }}
        </span>
    </div>

    {{-- Expanded State (progressive disclosure) --}}
    <div
        x-show="expanded"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
        class="point-expanded mt-4 space-y-3"
        style="display: none;"
        @click.stop
    >
        {{-- Description --}}
        <p class="description text-gray-700">
            {{ $pointViewModel->description }}
        </p>

        {{-- Justification --}}
        <p class="justification text-sm italic text-gray-600">
            {{ $pointViewModel->justification }}
        </p>

        {{-- Metadata --}}
        <div class="metadata flex items-center gap-4 text-sm text-gray-600 pt-2 border-t border-gray-200">
            <span class="flex items-center gap-1">
                <span>⏱️</span>
                <span>{{ $formattedDuration }}</span>
            </span>

            @if($pointViewModel->googleMapsUrl)
                <a
                    href="{{ $pointViewModel->googleMapsUrl }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="maps-link text-blue-600 hover:text-blue-800 underline flex items-center gap-1"
                    @click.stop
                >
                    <span>📍</span>
                    <span>Zobacz na mapie</span>
                </a>
            @endif
        </div>
    </div>
</div>
