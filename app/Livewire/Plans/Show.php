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

        // Check if there's an ongoing generation for this plan
        $this->checkForOngoingGeneration();
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
        return $this->limitService->getRemainingGenerations(Auth::id());
    }

    /**
     * Check if there's an ongoing generation when component mounts.
     */
    protected function checkForOngoingGeneration(): void
    {
        // Check if there's a pending OR processing generation for this plan
        $pendingGeneration = \App\Models\AIGeneration::where('travel_plan_id', $this->plan->id)
            ->whereIn('status', ['pending', 'processing'])
            ->orderBy('created_at', 'desc')
            ->first();

        if ($pendingGeneration) {
            // Check if generation is stuck (older than 3 minutes = 180 seconds)
            $ageInSeconds = now()->diffInSeconds($pendingGeneration->created_at);
            $maxAge = 180; // 3 minutes (job timeout is 120s + 60s buffer)

            if ($ageInSeconds > $maxAge) {
                // Mark as failed due to timeout
                $pendingGeneration->markAsFailed('Przekroczono limit czasu generowania (timeout)');
                Log::warning('AI generation timed out', [
                    'ai_generation_id' => $pendingGeneration->id,
                    'age_seconds' => $ageInSeconds,
                ]);
                session()->flash('error', 'Generowanie planu przekroczyło limit czasu. Spróbuj ponownie.');

                return;
            }

            $this->isGenerating = true;
            $this->generationId = $pendingGeneration->id;
            // Start with 15% to show that something is happening
            $this->generationProgress = 15;
        }
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
            // Start loading state with initial progress
            $this->isGenerating = true;
            $this->generationProgress = 15; // Start with 15% to show immediate feedback

            // Increment generation count with race condition protection
            $aiGeneration = $this->limitService->incrementGenerationCount(
                Auth::id(),
                $this->plan->id
            );

            // Store generation ID for polling BEFORE dispatching job
            $this->generationId = $aiGeneration->id;

            // Get user preferences
            $userPreferences = $this->preferenceService->getUserPreferences(Auth::id());

            // Dispatch to queue
            $job = GenerateTravelPlanJob::dispatch(
                travelPlanId: $this->plan->id,
                userId: Auth::id(),
                aiGenerationId: $aiGeneration->id,
                userPreferences: $userPreferences
            );

            // Use sync queue in local environment for immediate processing
            if (app()->environment(['local', 'development'])) {
                $job->onConnection('sync');

                // In sync mode, job executes immediately - reload and stop showing modal
                $this->isGenerating = false;
                $this->generationProgress = 100;
                $this->reloadPlan();
                session()->flash('success', 'Plan został pomyślnie wygenerowany!');
            } else {
                $job->onQueue('ai-generation');

                // In async mode, show progress modal
                session()->flash('success', 'Generowanie planu rozpoczęte. Zajmie to około 30-60 sekund...');
            }

            // Update remaining generations
            $this->aiGenerationsRemaining = $this->getUserAiGenerationsRemaining();
        } catch (LimitExceededException $e) {
            $this->isGenerating = false;
            $this->generationProgress = 0;
            session()->flash('error', $e->getMessage());
            Log::warning('Generation limit exceeded', ['user_id' => Auth::id()]);
        } catch (\Exception $e) {
            $this->isGenerating = false;
            $this->generationProgress = 0;

            // Reload plan to ensure UI is in sync (important for sync queue mode)
            $this->reloadPlan();

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
            $this->generationProgress = 0;
            session()->flash('error', 'Nie znaleziono informacji o generowaniu.');

            return;
        }

        // Check for timeout (job stuck for more than 3 minutes)
        $ageInSeconds = now()->diffInSeconds($aiGeneration->created_at);
        $maxAge = 180; // 3 minutes

        if (in_array($aiGeneration->status, ['pending', 'processing']) && $ageInSeconds > $maxAge) {
            // Mark as failed and stop polling
            $aiGeneration->markAsFailed('Przekroczono limit czasu generowania (timeout)');
            $this->isGenerating = false;
            $this->generationProgress = 0;
            session()->flash('error', 'Generowanie planu przekroczyło limit czasu. Spróbuj ponownie.');
            Log::warning('AI generation timed out during polling', [
                'ai_generation_id' => $aiGeneration->id,
                'age_seconds' => $ageInSeconds,
            ]);

            return;
        }

        if ($aiGeneration->status === 'completed') {
            $this->isGenerating = false;
            $this->generationProgress = 100;
            $this->reloadPlan();
            session()->flash('success', 'Plan został pomyślnie wygenerowany!');
        } elseif ($aiGeneration->status === 'failed') {
            $this->isGenerating = false;
            $this->generationProgress = 0;
            $errorMessage = $aiGeneration->error_message ?? 'Nieznany błąd';
            session()->flash('error', "Generowanie nie powiodło się: {$errorMessage}");
        } else {
            // Still pending or processing - estimate progress based on time elapsed
            $elapsed = now()->diffInSeconds($aiGeneration->created_at);
            $estimatedDuration = 60; // seconds (realistic estimation for AI generation)

            // Calculate progress with smooth curve
            // Start at 15%, grow fast initially, then slow down, cap at 95%
            if ($elapsed < 5) {
                // First 5 seconds: 15% -> 30%
                $calculatedProgress = 15 + ($elapsed / 5) * 15;
            } elseif ($elapsed < 15) {
                // 5-15 seconds: 30% -> 50%
                $calculatedProgress = 30 + (($elapsed - 5) / 10) * 20;
            } elseif ($elapsed < 30) {
                // 15-30 seconds: 50% -> 70%
                $calculatedProgress = 50 + (($elapsed - 15) / 15) * 20;
            } elseif ($elapsed < 50) {
                // 30-50 seconds: 70% -> 85%
                $calculatedProgress = 70 + (($elapsed - 30) / 20) * 15;
            } else {
                // 50+ seconds: 85% -> 95% (slow down near completion)
                $calculatedProgress = 85 + min(10, (($elapsed - 50) / 20) * 10);
            }

            // Ensure progress never decreases and caps at 95%
            $this->generationProgress = (int) min(95, max($this->generationProgress, $calculatedProgress));
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
