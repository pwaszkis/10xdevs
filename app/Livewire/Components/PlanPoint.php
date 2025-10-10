<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\DataTransferObjects\PlanPointViewModel;
use Livewire\Attributes\Prop;
use Livewire\Component;

class PlanPoint extends Component
{
    /**
     * @var array<string, mixed>
     */
    #[Prop]
    public array $point;

    /**
     * Get point ViewModel.
     */
    public function getPointViewModel(): PlanPointViewModel
    {
        return PlanPointViewModel::fromArray($this->point);
    }

    /**
     * Get formatted duration.
     */
    public function getFormattedDuration(): string
    {
        return $this->getPointViewModel()->getFormattedDuration();
    }

    /**
     * Get day part icon.
     */
    public function getDayPartIcon(): string
    {
        return $this->getPointViewModel()->getDayPartIcon();
    }

    /**
     * Get day part label.
     */
    public function getDayPartLabel(): string
    {
        return $this->getPointViewModel()->getDayPartLabel();
    }

    /**
     * Render the component.
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.components.plan-point');
    }
}
