<div class="plan-header bg-white rounded-lg shadow-md p-6 mb-6">
    {{-- Tytuł i status --}}
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-4">
        <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">
            {{ $plan->title }}
        </h1>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $this->getStatusBadgeClass() }}">
            {{ $this->getStatusLabel() }}
        </span>
    </div>

    {{-- Metadata --}}
    <div class="metadata grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Destynacja --}}
        <div class="metadata-item flex items-center gap-2 text-gray-700">
            <span class="text-xl">📍</span>
            <div>
                <p class="text-xs text-gray-500">Destynacja</p>
                <p class="font-medium">{{ $plan->destination }}</p>
            </div>
        </div>

        {{-- Daty --}}
        <div class="metadata-item flex items-center gap-2 text-gray-700">
            <span class="text-xl">📅</span>
            <div>
                <p class="text-xs text-gray-500">Termin</p>
                <p class="font-medium">{{ $this->getDateRange() }}</p>
            </div>
        </div>

        {{-- Liczba osób --}}
        <div class="metadata-item flex items-center gap-2 text-gray-700">
            <span class="text-xl">👥</span>
            <div>
                <p class="text-xs text-gray-500">Liczba osób</p>
                <p class="font-medium">{{ $plan->number_of_people }} {{ $plan->number_of_people === 1 ? 'osoba' : 'os.' }}</p>
            </div>
        </div>

        {{-- Budżet --}}
        @if($this->getFormattedBudget())
            <div class="metadata-item flex items-center gap-2 text-gray-700">
                <span class="text-xl">💰</span>
                <div>
                    <p class="text-xs text-gray-500">Budżet</p>
                    <p class="font-medium">{{ $this->getFormattedBudget() }}</p>
                </div>
            </div>
        @endif
    </div>
</div>
