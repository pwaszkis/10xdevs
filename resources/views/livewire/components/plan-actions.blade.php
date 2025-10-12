<div class="plan-actions flex flex-col sm:flex-row gap-4">
    {{-- Export to PDF --}}
    @if(!$this->isDraft())
        <div class="relative flex-1" x-data="{ showTooltip: false }">
            <button
                @if($this->canExportPdf())
                    @click="window.open('{{ route('plans.pdf', $travelPlanId) }}', '_blank')"
                @else
                    @mouseenter="showTooltip = true"
                    @mouseleave="showTooltip = false"
                    disabled
                @endif
                class="w-full px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition"
            >
                📄 Export do PDF
            </button>

            @if($this->getExportPdfTooltip())
                <div
                    x-show="showTooltip"
                    x-transition
                    class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-800 text-white text-sm rounded shadow-lg whitespace-nowrap"
                    style="display: none;"
                >
                    {{ $this->getExportPdfTooltip() }}
                    <div class="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-gray-800"></div>
                </div>
            @endif
        </div>
    @endif

    {{-- Regenerate Plan --}}
    @if(!$this->isDraft())
        <div class="relative flex-1" x-data="{ showTooltip: false }">
            <button
                @if($this->canRegenerate())
                    wire:click="$parent.regeneratePlan"
                @else
                    @mouseenter="showTooltip = true"
                    @mouseleave="showTooltip = false"
                    disabled
                @endif
                class="w-full px-6 py-3 bg-gray-200 text-gray-800 font-medium rounded-lg hover:bg-gray-300 disabled:bg-gray-100 disabled:cursor-not-allowed disabled:text-gray-500 transition"
            >
                🔄 Regeneruj plan
            </button>

            @if($this->getRegenerateTooltip())
                <div
                    x-show="showTooltip"
                    x-transition
                    class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-800 text-white text-sm rounded shadow-lg whitespace-nowrap z-10"
                    style="display: none;"
                >
                    {{ $this->getRegenerateTooltip() }}
                    <div class="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-gray-800"></div>
                </div>
            @endif
        </div>
    @endif

    {{-- Delete Plan --}}
    <div class="relative flex-1">
        <button
            wire:click="$parent.deletePlan"
            class="w-full px-6 py-3 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition"
        >
            🗑️ Usuń plan
        </button>
    </div>
</div>
