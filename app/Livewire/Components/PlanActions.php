<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use Livewire\Attributes\Prop;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class PlanActions extends Component
{
    #[Prop]
    #[Reactive]
    public string $status;

    #[Prop]
    #[Reactive]
    public int $aiGenerationsRemaining;

    #[Prop]
    #[Reactive]
    public bool $hasAiPlan;

    #[Prop]
    public int $travelPlanId;

    /**
     * Check if can export PDF.
     */
    public function canExportPdf(): bool
    {
        return in_array($this->status, ['planned', 'completed'])
            && $this->hasAiPlan === true;
    }

    /**
     * Check if can regenerate.
     */
    public function canRegenerate(): bool
    {
        return $this->status !== 'draft'
            && $this->aiGenerationsRemaining > 0;
    }

    /**
     * Check if is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Get regenerate tooltip.
     */
    public function getRegenerateTooltip(): ?string
    {
        if ($this->status === 'draft') {
            return 'Najpierw wygeneruj plan';
        }

        if ($this->aiGenerationsRemaining === 0) {
            return 'Osiągnięto limit generowań (10/10). Reset: 1. następnego miesiąca';
        }

        return null;
    }

    /**
     * Get export PDF tooltip.
     */
    public function getExportPdfTooltip(): ?string
    {
        if ($this->status === 'draft') {
            return 'Wygeneruj plan, aby eksportować';
        }

        if (! $this->hasAiPlan) {
            return 'Poczekaj na zakończenie generowania';
        }

        return null;
    }

    /**
     * Render the component.
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.components.plan-actions');
    }
}
