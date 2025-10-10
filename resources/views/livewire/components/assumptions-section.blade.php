<div class="assumptions-section bg-gray-50 rounded-lg p-6 mb-6" x-data="{ showAssumptions: false }">
    {{-- Toggle button --}}
    <button
        @click="showAssumptions = !showAssumptions"
        class="assumptions-toggle flex items-center justify-between w-full text-left font-medium text-gray-900 hover:text-blue-600 transition"
        :aria-expanded="showAssumptions"
    >
        <span class="text-lg">Zobacz Twoje założenia</span>
        <svg
            class="w-5 h-5 transition-transform"
            :class="{ 'rotate-180': showAssumptions }"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>

    {{-- Content (collapsible) --}}
    <div
        x-show="showAssumptions"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
        class="assumptions-content mt-4 space-y-4"
        style="display: none;"
    >
        {{-- User Notes --}}
        @if($userNotes)
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Twoje notatki:</h3>
                <p class="text-gray-600 whitespace-pre-wrap">{{ $userNotes }}</p>
            </div>
        @endif

        {{-- Preferences --}}
        @php
            $preferencesDto = $this->getPreferencesDto();
        @endphp

        @if($preferencesDto)
            {{-- Interest Categories --}}
            @if(!empty($preferencesDto->interestsCategories))
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 mb-2">Twoje zainteresowania:</h3>
                    <div class="preference-badges flex flex-wrap gap-2">
                        @foreach($preferencesDto->getReadableCategories() as $category)
                            <livewire:components.preference-badge
                                :category="$category"
                                :key="'cat-' . $loop->index"
                            />
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Practical Parameters --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @if($preferencesDto->getReadableTravelPace())
                    <div class="bg-white rounded-lg p-3">
                        <p class="text-xs text-gray-500">Tempo podróży</p>
                        <p class="font-medium text-gray-900">{{ $preferencesDto->getReadableTravelPace() }}</p>
                    </div>
                @endif

                @if($preferencesDto->getReadableBudgetLevel())
                    <div class="bg-white rounded-lg p-3">
                        <p class="text-xs text-gray-500">Poziom budżetu</p>
                        <p class="font-medium text-gray-900">{{ $preferencesDto->getReadableBudgetLevel() }}</p>
                    </div>
                @endif

                @if($preferencesDto->getReadableTransportPreference())
                    <div class="bg-white rounded-lg p-3">
                        <p class="text-xs text-gray-500">Transport</p>
                        <p class="font-medium text-gray-900">{{ $preferencesDto->getReadableTransportPreference() }}</p>
                    </div>
                @endif

                @if($preferencesDto->getReadableRestrictions())
                    <div class="bg-white rounded-lg p-3">
                        <p class="text-xs text-gray-500">Ograniczenia</p>
                        <p class="font-medium text-gray-900">{{ $preferencesDto->getReadableRestrictions() }}</p>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
