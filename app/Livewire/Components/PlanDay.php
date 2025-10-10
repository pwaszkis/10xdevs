<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\DataTransferObjects\PlanDayViewModel;
use Livewire\Attributes\Prop;
use Livewire\Component;

class PlanDay extends Component
{
    /**
     * @var array<string, mixed>
     */
    #[Prop]
    public array $day;

    #[Prop]
    public bool $expanded = false;

    #[Prop]
    public bool $isMobile = false;

    /**
     * Get day ViewModel.
     */
    public function getDayViewModel(): PlanDayViewModel
    {
        return PlanDayViewModel::fromArray($this->day);
    }

    /**
     * Get points grouped by day part.
     *
     * @return array<string, array<int, mixed>>
     */
    public function getPointsByDayPart(): array
    {
        $viewModel = $this->getDayViewModel();

        return $viewModel->getPointsByDayPart();
    }

    /**
     * Get day part label.
     */
    public function getDayPartLabel(string $dayPart): string
    {
        return match ($dayPart) {
            'rano' => 'Rano',
            'poludnie' => 'Południe',
            'popołudnie' => 'Popołudnie',
            'wieczór' => 'Wieczór',
            default => 'Inne',
        };
    }

    /**
     * Render the component.
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.components.plan-day');
    }
}
