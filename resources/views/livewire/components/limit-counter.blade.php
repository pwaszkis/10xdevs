<div class="flex items-center gap-2" x-data="{ showTooltip: false }">
    <div class="relative">
        <span
            class="text-sm font-semibold {{ $colorClass }}"
            @mouseenter="showTooltip = true"
            @mouseleave="showTooltip = false"
        >
            {{ $used }}/{{ $limit }}
        </span>

        <div
            x-show="showTooltip"
            x-transition
            class="absolute left-0 top-full mt-2 w-48 px-3 py-2 text-xs bg-gray-900 dark:bg-gray-700 text-white rounded shadow-lg z-50"
            style="display: none;"
        >
            Generacje AI w tym miesiącu.<br>
            Reset: {{ $resetDate }}
        </div>
    </div>
</div>
