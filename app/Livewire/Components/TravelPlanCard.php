<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Models\TravelPlan;
use Livewire\Attributes\Prop;
use Livewire\Component;

/**
 * Travel Plan Card Component
 *
 * Reusable card component for displaying travel plan summary.
 * Used in dashboard and plan lists.
 */
class TravelPlanCard extends Component
{
    // ==================== PROPS ====================

    #[Prop]
    public TravelPlan $plan;

    // ==================== COMPUTED PROPERTIES ====================

    /**
     * Get status badge color class.
     */
    public function statusColorClass(): string
    {
        return match ($this->plan->status) {
            'draft' => 'bg-gray-100 text-gray-800',
            'planned' => 'bg-blue-100 text-blue-800',
            'completed' => 'bg-green-100 text-green-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get status label in Polish.
     */
    public function statusLabel(): string
    {
        return match ($this->plan->status) {
            'draft' => 'Szkic',
            'planned' => 'Zaplanowany',
            'completed' => 'Zrealizowany',
            default => 'Nieznany',
        };
    }

    /**
     * Get formatted date range.
     */
    public function dateRange(): string
    {
        if (! $this->plan->departure_date) {
            return 'Brak daty';
        }

        $start = $this->plan->departure_date->translatedFormat('j M Y');

        if ($this->plan->end_date) {
            $end = $this->plan->end_date->translatedFormat('j M Y');

            return "{$start} - {$end}";
        }

        return $start;
    }

    /**
     * Get formatted duration.
     */
    public function duration(): string
    {
        $days = $this->plan->number_of_days;

        if ($days === 1) {
            return '1 dzień';
        }

        return "{$days} dni";
    }

    /**
     * Get formatted budget.
     */
    public function budget(): ?string
    {
        if (! $this->plan->budget_per_person) {
            return null;
        }

        $amount = number_format($this->plan->budget_per_person, 0, ',', ' ');
        $currency = $this->plan->budget_currency ?? 'PLN';

        return "{$amount} {$currency}/os.";
    }

    /**
     * Get total budget.
     */
    public function totalBudget(): ?string
    {
        if (! $this->plan->total_budget) {
            return null;
        }

        $amount = number_format($this->plan->total_budget, 0, ',', ' ');
        $currency = $this->plan->budget_currency ?? 'PLN';

        return "{$amount} {$currency}";
    }

    /**
     * Check if plan has AI-generated content.
     */
    public function hasAiPlan(): bool
    {
        return $this->plan->has_ai_plan;
    }

    // ==================== RENDER ====================

    /**
     * Render the component.
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.components.travel-plan-card');
    }
}
