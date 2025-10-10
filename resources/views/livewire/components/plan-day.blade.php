@php
    $dayViewModel = $this->getDayViewModel();
    $pointsByDayPart = $this->getPointsByDayPart();
@endphp

<div
    class="plan-day-card bg-white rounded-lg shadow-md mb-4 overflow-hidden"
    x-data="{ expanded: @js($expanded) }"
>
    {{-- Day Header (clickable) --}}
    <button
        @click="expanded = !expanded"
        @keydown.enter="expanded = !expanded"
        @keydown.space.prevent="expanded = !expanded"
        class="day-header flex items-center justify-between w-full p-4 text-left hover:bg-gray-50 transition cursor-pointer"
        :aria-expanded="expanded"
        aria-controls="day-{{ $day['id'] }}-content"
    >
        <div>
            <h3 class="text-xl font-semibold text-gray-900">
                Dzień {{ $dayViewModel->dayNumber }} - {{ $dayViewModel->date->format('d.m.Y') }}
            </h3>
            @if($dayViewModel->summary)
                <p class="text-sm text-gray-600 mt-1">{{ $dayViewModel->summary }}</p>
            @endif
        </div>

        <svg
            class="w-6 h-6 text-gray-600 transition-transform"
            :class="{ 'rotate-180': expanded }"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>

    {{-- Day Content (collapsible) --}}
    <div
        x-show="expanded"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 max-h-0"
        x-transition:enter-end="opacity-100 max-h-screen"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 max-h-screen"
        x-transition:leave-end="opacity-0 max-h-0"
        id="day-{{ $day['id'] }}-content"
        class="day-content p-4 bg-gray-50 border-t border-gray-200"
        style="display: none;"
    >
        @if(!empty($pointsByDayPart))
            @foreach(['rano', 'poludnie', 'popołudnie', 'wieczór'] as $dayPart)
                @if(isset($pointsByDayPart[$dayPart]))
                    <div class="day-part-section mb-6 last:mb-0">
                        <h4 class="text-lg font-medium text-gray-700 mb-3 flex items-center gap-2">
                            <span class="text-2xl">
                                @if($dayPart === 'rano') 🌅
                                @elseif($dayPart === 'poludnie') ☀️
                                @elseif($dayPart === 'popołudnie') 🌇
                                @else 🌙
                                @endif
                            </span>
                            <span>{{ $this->getDayPartLabel($dayPart) }}</span>
                        </h4>

                        <div class="space-y-3">
                            @foreach($pointsByDayPart[$dayPart] as $point)
                                <livewire:components.plan-point
                                    :point="$point"
                                    :key="'point-' . $point['id']"
                                />
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        @else
            <p class="text-gray-500 text-center py-4">
                Brak punktów dla tego dnia.
            </p>
        @endif
    </div>
</div>
