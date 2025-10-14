<?php

declare(strict_types=1);

namespace App\Livewire\Plans;

use App\Exceptions\LimitExceededException;
use App\Jobs\GenerateTravelPlanJob;
use App\Models\TravelPlan;
use App\Models\TravelPlanFeedback;
use App\Services\LimitService;
use App\Services\PreferenceService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Szczegóły planu')]
#[Layout('components.layouts.app')]
class Show extends Component
{
    // ==================== DEPENDENCY INJECTION ====================

    protected LimitService $limitService;

    protected PreferenceService $preferenceService;

    /**
     * Boot method for dependency injection.
     */
    public function boot(
        LimitService $limitService,
        PreferenceService $preferenceService
    ): void {
        $this->limitService = $limitService;
        $this->preferenceService = $preferenceService;
    }

    // ==================== PROPERTIES ====================

    // Properties - Plan data
    public TravelPlan $plan;

    public ?TravelPlanFeedback $feedback = null;

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

    /**
     * Mount the component.
     */
    public function mount(TravelPlan $plan): void
    {
        // Check authorization
        if ($plan->user_id !== auth()->id()) {
            abort(403, 'Ten plan nie należy do Ciebie.');
        }

        // Load plan with relationships
        $plan->load(['days.points', 'feedback', 'user.preferences']);

        $this->plan = $plan;
        $this->feedback = $plan->feedback;
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
     * Reload plan data from database.
     */
    protected function reloadPlan(): void
    {
        $this->plan->load(['days.points', 'feedback', 'user.preferences']);
        $this->plan->refresh();
        $this->feedback = $this->plan->feedback;
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
     * Check if plan can be regenerated (or generated for the first time from draft).
     */
    #[Computed]
    public function canRegenerate(): bool
    {
        // Allow generation for drafts (first time) and regeneration for already generated plans
        return $this->aiGenerationsRemaining > 0;
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
     * Show regenerate confirmation modal (or generate directly for drafts).
     */
    public function regeneratePlan(): void
    {
        if (! $this->canRegenerate()) {
            session()->flash('error', 'Nie można wygenerować planu. Sprawdź limit generowań.');

            return;
        }

        // For drafts, generate directly without confirmation modal
        if ($this->plan->status === 'draft') {
            $this->confirmRegenerate();

            return;
        }

        // For already generated plans, show confirmation modal
        $this->showRegenerateModal = true;
    }

    /**
     * Confirm and execute plan regeneration.
     */
    public function confirmRegenerate(): void
    {
        $this->showRegenerateModal = false;

        // Check limits
        if ($this->aiGenerationsRemaining <= 0) {
            session()->flash('error', "Osiągnięto limit generowań ({$this->aiGenerationsLimit}/miesiąc).");

            return;
        }

        try {
            // Start loading state
            $this->isGenerating = true;

            // Increment generation count with race condition protection
            $aiGeneration = $this->limitService->incrementGenerationCount(
                Auth::id(),
                $this->plan->id
            );

            // Get user preferences
            $userPreferences = $this->preferenceService->getUserPreferences(Auth::id());

            // Dispatch to queue (sync in local env due to PHP version constraints)
            $job = GenerateTravelPlanJob::dispatch(
                travelPlanId: $this->plan->id,
                userId: Auth::id(),
                aiGenerationId: $aiGeneration->id,
                userPreferences: $userPreferences
            );

            // Use sync queue in local environment
            if (app()->environment(['local', 'development'])) {
                $job->onConnection('sync');
            } else {
                $job->onQueue('ai-generation');
            }

            // Store generation ID for polling
            $this->generationId = $aiGeneration->id;
            $this->generationProgress = 0;

            // Update remaining generations
            $this->aiGenerationsRemaining = $this->getUserAiGenerationsRemaining();

            session()->flash('success', 'Generowanie planu rozpoczęte. Zajmie to około 30 sekund...');
        } catch (LimitExceededException $e) {
            $this->isGenerating = false;
            session()->flash('error', $e->getMessage());
            Log::warning('Generation limit exceeded', ['user_id' => Auth::id()]);
        } catch (\Exception $e) {
            $this->isGenerating = false;
            session()->flash('error', 'Wystąpił problem z generowaniem planu. Spróbuj ponownie.');
            Log::error('Failed to start plan generation', [
                'user_id' => Auth::id(),
                'plan_id' => $this->plan->id,
                'error' => $e->getMessage(),
            ]);
        }
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

        // Check AI generation status from database
        $aiGeneration = \App\Models\AIGeneration::find($this->generationId);

        if (! $aiGeneration) {
            $this->isGenerating = false;
            session()->flash('error', 'Nie znaleziono informacji o generowaniu.');

            return;
        }

        if ($aiGeneration->status === 'completed') {
            $this->isGenerating = false;
            $this->generationProgress = 100;
            $this->reloadPlan();
            session()->flash('success', 'Plan został pomyślnie wygenerowany!');
        } elseif ($aiGeneration->status === 'failed') {
            $this->isGenerating = false;
            $errorMessage = $aiGeneration->error_message ?? 'Nieznany błąd';
            session()->flash('error', "Generowanie nie powiodło się: {$errorMessage}");
        } else {
            // Processing - estimate progress based on time elapsed
            $elapsed = now()->diffInSeconds($aiGeneration->created_at);
            $estimatedDuration = 30; // seconds
            // Cap at 90% until completion, prevent going over 100%
            $calculatedProgress = ($elapsed / $estimatedDuration) * 100;
            $this->generationProgress = (int) min(90, max(0, $calculatedProgress));
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
     * Render the component.
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.plans.show');
    }
}
