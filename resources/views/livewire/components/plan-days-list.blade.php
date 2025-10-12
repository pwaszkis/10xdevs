<div class="plan-days-section mb-6">
    <h2 class="text-2xl font-bold text-gray-900 mb-4">
        Plan dnia po dniu
    </h2>

    @foreach($days->take($loadedDaysCount) as $index => $day)
        <div wire:key="day-{{ $day->id }}">
            <x-plan.day
                :day="[
                    'id' => $day->id,
                    'day_number' => $day->day_number,
                    'date' => $day->date->format('Y-m-d'),
                    'summary' => $day->summary,
                    'points' => $day->points->map(fn($p) => [
                        'id' => $p->id,
                        'order_number' => $p->order_number,
                        'day_part' => $p->day_part,
                        'name' => $p->name,
                        'description' => $p->description,
                        'justification' => $p->justification,
                        'duration_minutes' => $p->duration_minutes,
                        'google_maps_url' => $p->google_maps_url,
                        'location_lat' => $p->location_lat,
                        'location_lng' => $p->location_lng,
                    ])->toArray()
                ]"
                :expanded="$index === 0 && !request()->header('X-Mobile')"
            />
        </div>
    @endforeach

    @if($days->count() > $loadedDaysCount)
        <div class="text-center mt-4">
            <button
                wire:click="loadMoreDays"
                class="px-6 py-3 bg-gray-200 text-gray-800 font-medium rounded-lg hover:bg-gray-300 transition"
            >
                Pokaż więcej dni ({{ $days->count() - $loadedDaysCount }} pozostałych)
            </button>
        </div>
    @endif
</div>
