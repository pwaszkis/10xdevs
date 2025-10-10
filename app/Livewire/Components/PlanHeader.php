<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Models\TravelPlan;
use Livewire\Attributes\Prop;
use Livewire\Component;

class PlanHeader extends Component
{
    #[Prop]
    public TravelPlan $plan;

    /**
     * Get status badge color classes.
     */
    public function getStatusBadgeClass(): string
    {
        return match ($this->plan->status) {
            'draft' => 'bg-gray-100 text-gray-800',
            'planned' => 'bg-blue-100 text-blue-800',
            'completed' => 'bg-green-100 text-green-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get status label.
     */
    public function getStatusLabel(): string
    {
        return match ($this->plan->status) {
            'draft' => 'Szkic',
            'planned' => 'Zaplanowane',
            'completed' => 'Zrealizowane',
            default => 'Nieznany',
        };
    }

    /**
     * Format date range.
     */
    public function getDateRange(): string
    {
        $start = $this->plan->departure_date->format('d.m.Y');
        $end = $this->plan->departure_date->addDays($this->plan->number_of_days - 1)->format('d.m.Y');

        return "od {$start} do {$end}";
    }

    /**
     * Format budget.
     */
    public function getFormattedBudget(): ?string
    {
        if (! $this->plan->budget_per_person) {
            return null;
        }

        $amount = number_format($this->plan->budget_per_person, 2, ',', ' ');
        $currency = $this->plan->budget_currency ?? 'PLN';

        return "{$amount} {$currency} / os.";
    }

    /**
     * Render the component.
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.components.plan-header');
    }
}
