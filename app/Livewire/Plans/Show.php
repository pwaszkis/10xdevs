<?php

declare(strict_types=1);

namespace App\Livewire\Plans;

use App\Models\Feedback;
use App\Models\TravelPlan;
use Illuminate\Support\Facades\Http;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Szczegóły planu')]
class Show extends Component
{
    // Properties - Plan data
    public TravelPlan $plan;

    public ?Feedback $feedback = null;

    // UI State
    public bool $showDeleteModal = false;

    public bool $showRegenerateModal = false;

    public bool $isExportingPdf = false;

    public bool $isGenerating = false;

    public int $generationProgress = 0;

    public ?int $generationId = null;

    // User context
    public int $aiGenerationsRemaining;

    public int $aiGenerationsLimit = 10;

    // Lazy loading
    public int $loadedDaysCount = 3;

    /**
     * Mount the component.
     */
    public function mount(int $id): void
    {
        $this->loadPlan($id);
        $this->loadUserContext();
    }

    /**
     * Hydrate the component after each request.
     */
    public function hydrate(): void
    {
        // Refresh user context (AI limits may have changed)
        $this->aiGenerationsRemaining = $this->getUserAiGenerationsRemaining();
    }

    /**
     * Load plan data from database.
     */
    protected function loadPlan(int $id): void
    {
        // Load plan with relationships
        $plan = TravelPlan::with(['days.points', 'feedback', 'user.preferences'])->find($id);

        if (! $plan) {
            abort(404, 'Plan nie został znaleziony.');
        }

        if ($plan->user_id !== auth()->id()) {
            abort(403, 'Ten plan nie należy do Ciebie.');
        }

        $this->plan = $plan;
        $this->feedback = $plan->feedback;
    }

    /**
     * Load user context (AI limits).
     */
    protected function loadUserContext(): void
    {
        $this->aiGenerationsRemaining = $this->getUserAiGenerationsRemaining();
    }

    /**
     * Get user's remaining AI generations.
     */
    protected function getUserAiGenerationsRemaining(): int
    {
        $user = auth()->user();

        return max(0, $this->aiGenerationsLimit - $user->ai_generations_count_current_month);
    }

    /**
     * Check if plan can be regenerated.
     */
    #[Computed]
    public function canRegenerate(): bool
    {
        return $this->plan->status !== 'draft'
            && $this->aiGenerationsRemaining > 0;
    }

    /**
     * Check if plan can be exported to PDF.
     */
    #[Computed]
    public function canExportPdf(): bool
    {
        return in_array($this->plan->status, ['planned', 'completed'])
            && $this->plan->has_ai_plan === true;
    }

    /**
     * Show delete confirmation modal.
     */
    public function deletePlan(): void
    {
        $this->showDeleteModal = true;
    }

    /**
     * Confirm and execute plan deletion.
     */
    public function confirmDelete(): mixed
    {
        // TEMPORARY: Direct database deletion instead of API call
        try {
            $this->plan->delete();
            session()->flash('success', 'Plan został usunięty.');

            return $this->redirect(route('dashboard'));
        } catch (\Exception $e) {
            session()->flash('error', 'Nie udało się usunąć planu. Spróbuj ponownie.');
            $this->showDeleteModal = false;

            return null;
        }
    }

    /**
     * Show regenerate confirmation modal.
     */
    public function regeneratePlan(): void
    {
        if (! $this->canRegenerate()) {
            session()->flash('error', 'Nie można regenerować planu.');

            return;
        }

        $this->showRegenerateModal = true;
    }

    /**
     * Confirm and execute plan regeneration.
     */
    public function confirmRegenerate(): void
    {
        // TEMPORARY: Mock regeneration instead of API call
        // TODO: Implement actual API call to generate AI plan
        if ($this->aiGenerationsRemaining <= 0) {
            session()->flash('error', "Osiągnięto limit generowań ({$this->aiGenerationsLimit}/miesiąc).");
            $this->showRegenerateModal = false;

            return;
        }

        // Mock: Set generating state
        $this->isGenerating = true;
        $this->generationId = rand(1, 1000);
        $this->generationProgress = 0;

        $this->showRegenerateModal = false;
    }

    /**
     * Poll generation status (called every 3 seconds during generation).
     */
    #[On('poll-generation-status')]
    public function checkGenerationStatus(): void
    {
        if (! $this->isGenerating || ! $this->generationId) {
            return;
        }

        $response = Http::get("/api/travel-plans/{$this->plan->id}/generation-status");

        if ($response->successful()) {
            $status = $response->json('data.status');

            if ($status === 'completed') {
                $this->isGenerating = false;
                $this->generationProgress = 100;
                $this->loadPlan($this->plan->id);
                session()->flash('success', 'Plan został pomyślnie wygenerowany!');
            } elseif ($status === 'failed') {
                $this->isGenerating = false;
                $errorMessage = $response->json('data.error_message');
                session()->flash('error', "Generowanie nie powiodło się: {$errorMessage}");
            } else {
                // Processing
                $this->generationProgress = $response->json('data.progress_percentage', 0);
            }
        }
    }

    /**
     * Export plan to PDF.
     */
    public function exportPdf(): mixed
    {
        if (! $this->canExportPdf()) {
            session()->flash('error', 'Nie można eksportować szkicu planu.');

            return null;
        }

        $this->isExportingPdf = true;

        // Redirect to PDF endpoint (browser will trigger download)
        return $this->redirect("/api/travel-plans/{$this->plan->id}/pdf", navigate: false);
    }

    /**
     * Load more days (lazy loading).
     */
    public function loadMoreDays(): void
    {
        $this->loadedDaysCount = min(
            $this->loadedDaysCount + 5,
            $this->plan->days->count()
        );
    }

    /**
     * Render the component.
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.plans.show');
    }
}
