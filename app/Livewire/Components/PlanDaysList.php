<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Models\PlanDay;
use Illuminate\Support\Collection;
use Livewire\Attributes\Prop;
use Livewire\Component;

class PlanDaysList extends Component
{
    /**
     * @var Collection<int, PlanDay>
     */
    #[Prop]
    public Collection $days;

    public int $loadedDaysCount = 3;

    /**
     * Load more days (lazy loading).
     */
    public function loadMoreDays(): void
    {
        $this->loadedDaysCount = min(
            $this->loadedDaysCount + 5,
            $this->days->count()
        );
    }

    /**
     * Render the component.
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.components.plan-days-list');
    }
}
