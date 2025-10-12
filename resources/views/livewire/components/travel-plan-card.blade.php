<a href="{{ route('plans.show', $plan->id) }}"
   wire:navigate
   class="block bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-all duration-200 group">

    {{-- Card Header --}}
    <div class="p-6">
        <div class="flex items-start justify-between mb-3">
            <h3 class="text-lg font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors line-clamp-1">
                {{ $plan->title }}
            </h3>
            <span class="ml-2 px-2.5 py-1 text-xs font-medium rounded-full whitespace-nowrap {{ $this->statusColorClass() }}">
                {{ $this->statusLabel() }}
            </span>
        </div>

        {{-- Destination --}}
        <div class="flex items-center text-gray-600 mb-4">
            <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <span class="text-sm line-clamp-1">{{ $plan->destination }}</span>
        </div>

        {{-- Plan Details Grid --}}
        <div class="space-y-2.5">
            {{-- Date Range --}}
            <div class="flex items-center text-sm text-gray-700">
                <svg class="w-4 h-4 mr-2 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span class="line-clamp-1">{{ $this->dateRange() }}</span>
            </div>

            {{-- Duration & People --}}
            <div class="flex items-center text-sm text-gray-700">
                <svg class="w-4 h-4 mr-2 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ $this->duration() }} • {{ $plan->number_of_people }} {{ $plan->number_of_people === 1 ? 'osoba' : 'osoby' }}</span>
            </div>

            {{-- Budget --}}
            @if($this->budget())
                <div class="flex items-center text-sm text-gray-700">
                    <svg class="w-4 h-4 mr-2 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>{{ $this->budget() }}</span>
                    @if($this->totalBudget() && $plan->number_of_people > 1)
                        <span class="text-gray-500 ml-1">(razem: {{ $this->totalBudget() }})</span>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Card Footer --}}
    <div class="px-6 py-3 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
        <div class="flex items-center text-xs text-gray-500">
            @if($this->hasAiPlan())
                <svg class="w-4 h-4 mr-1.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
                <span class="font-medium text-indigo-600">Plan AI wygenerowany</span>
            @else
                <svg class="w-4 h-4 mr-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span>Szkic planu</span>
            @endif
        </div>

        <svg class="w-5 h-5 text-gray-400 group-hover:text-indigo-600 group-hover:translate-x-1 transition-all duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </div>
</a>
