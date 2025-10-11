<?php

declare(strict_types=1);

namespace App\Livewire\Plans;

use App\Exceptions\LimitExceededException;
use App\Jobs\GenerateTravelPlanJob;
use App\Models\TravelPlan;
use App\Services\LimitService;
use App\Services\PreferenceService;
use App\Services\TravelService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;

/**
 * Create Plan Form Component
 *
 * Livewire component for creating and editing travel plans.
 * Handles form validation, draft saving, and AI plan generation.
 */
#[Layout('components.layouts.app')]
class CreatePlanForm extends Component
{
    // ==================== DEPENDENCY INJECTION ====================

    protected TravelService $travelService;

    protected LimitService $limitService;

    protected PreferenceService $preferenceService;

    /**
     * Boot method for dependency injection.
     */
    public function boot(
        TravelService $travelService,
        LimitService $limitService,
        PreferenceService $preferenceService
    ): void {
        $this->travelService = $travelService;
        $this->limitService = $limitService;
        $this->preferenceService = $preferenceService;
    }

    // ==================== COMPONENT STATE ====================

    public ?int $travelId = null;

    public bool $editMode = false;

    // ==================== FORM DATA ====================

    #[Rule('required|string|max:255')]
    public string $title = '';

    #[Rule('required|string|max:255')]
    public string $destination = '';

    #[Rule('required|date|after:today')]
    public ?string $departure_date = null;

    #[Rule('required|integer|min:1|max:30')]
    public int $number_of_days = 7;

    #[Rule('required|integer|min:1|max:10')]
    public int $number_of_people = 2;

    #[Rule('nullable|numeric|min:0')]
    public ?float $budget_per_person = null;

    #[Rule('nullable|string|in:PLN,EUR,USD,GBP')]
    public string $budget_currency = 'PLN';

    #[Rule('nullable|string|max:5000')]
    public ?string $user_notes = null;

    // ==================== UI STATE ====================

    public bool $isGenerating = false;

    public bool $canGenerate = true;

    public int $generationsUsed = 0;

    public int $generationsLimit = 10;

    /** @var array<string> */
    public array $currencies = ['PLN', 'EUR', 'USD', 'GBP'];

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    // ==================== LIFECYCLE HOOKS ====================

    /**
     * Mount component - initialize state.
     */
    public function mount(?int $travelId = null): void
    {
        $this->travelId = $travelId;
        $this->editMode = $travelId !== null;

        // Check limits when creating new plan
        if (! $this->editMode) {
            $this->checkUserLimit();
        }

        // Load data when editing
        if ($this->editMode) {
            $this->loadTravelData();
        }
    }

    /**
     * Updated hook - real-time validation and calculations.
     */
    public function updated(string $property): void
    {
        // Validate specific field
        $this->validateOnly($property);

        // Dynamic calculations
        match ($property) {
            'departure_date', 'number_of_days' => $this->dispatch('date-calculated'),
            'budget_per_person', 'number_of_people' => $this->dispatch('budget-calculated'),
            default => null
        };
    }

    // ==================== PUBLIC ACTIONS ====================

    /**
     * Save plan as draft (no AI generation).
     */
    public function saveAsDraft(): void
    {
        $validated = $this->validate();

        try {
            $planData = $this->preparePlanData($validated, 'draft');

            $travel = $this->editMode
                ? $this->travelService->update($this->travelId, $planData)
                : $this->travelService->create($planData);

            // Redirect using JavaScript
            $this->js("window.location.href = '".route('plans.show', $travel->id)."'");

        } catch (\Exception $e) {
            $this->handleSaveError($e);
        }
    }

    /**
     * Generate AI-powered travel plan.
     */
    public function generatePlan(): void
    {
        $validated = $this->validate();

        // Check limits
        if (! $this->canGeneratePlan()) {
            $this->errorMessage = $this->getLimitExceededMessage();

            return;
        }

        try {
            // Start loading state
            $this->isGenerating = true;
            $this->errorMessage = null;

            // Prepare plan data (draft status until AI generation completes)
            $planData = $this->preparePlanData($validated, 'draft');

            // Save or update plan
            $travel = $this->editMode
                ? $this->travelService->update($this->travelId, $planData)
                : $this->travelService->create($planData);

            // Increment generation count with race condition protection
            $aiGeneration = $this->limitService->incrementGenerationCount(
                Auth::id(),
                $travel->id
            );

            // Get user preferences
            $userPreferences = $this->preferenceService->getUserPreferences(Auth::id());

            // Dispatch to queue (sync in local env due to PHP version constraints)
            $job = GenerateTravelPlanJob::dispatch(
                travelPlanId: $travel->id,
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

            // Redirect using JavaScript
            $this->js("window.location.href = '".route('plans.show', $travel->id)."'");

        } catch (LimitExceededException $e) {
            $this->handleLimitError($e);
        } catch (\Exception $e) {
            $this->handleGenerationError($e);
        } finally {
            $this->isGenerating = false;
        }
    }

    // ==================== PRIVATE METHODS ====================

    /**
     * Check user's generation limits.
     */
    private function checkUserLimit(): void
    {
        $this->generationsUsed = $this->limitService->getGenerationCount(Auth::id());
        $this->generationsLimit = LimitService::MONTHLY_LIMIT;
        $this->canGenerate = $this->generationsUsed < $this->generationsLimit;
    }

    /**
     * Check if user can generate plan.
     */
    private function canGeneratePlan(): bool
    {
        $userId = Auth::id();
        $this->generationsUsed = $this->limitService->getGenerationCount($userId);
        $this->canGenerate = $this->generationsUsed < $this->generationsLimit;

        return $this->canGenerate;
    }

    /**
     * Prepare plan data for saving.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function preparePlanData(array $validated, string $status = 'draft'): array
    {
        return [
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'destination' => $validated['destination'],
            'departure_date' => $validated['departure_date'],
            'number_of_days' => $validated['number_of_days'],
            'number_of_people' => $validated['number_of_people'],
            'budget_per_person' => $validated['budget_per_person'],
            'budget_currency' => $this->budget_currency,
            'user_notes' => $validated['user_notes'],
            'status' => $status,
        ];
    }

    /**
     * Load travel data for editing.
     */
    private function loadTravelData(): void
    {
        /** @var TravelPlan $travel */
        $travel = TravelPlan::findOrFail($this->travelId);

        // Authorization check
        if ($travel->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        // Load data to component properties
        $this->title = $travel->title;
        $this->destination = $travel->destination;
        $this->departure_date = $travel->departure_date->format('Y-m-d');
        $this->number_of_days = $travel->number_of_days;
        $this->number_of_people = $travel->number_of_people;
        $this->budget_per_person = $travel->budget_per_person;
        $this->budget_currency = $travel->budget_currency ?? 'PLN';
        $this->user_notes = $travel->user_notes;
    }

    /**
     * Calculate end date.
     */
    private function calculateEndDate(): ?\Carbon\Carbon
    {
        if (empty($this->departure_date) || empty($this->number_of_days)) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($this->departure_date)
                ->addDays($this->number_of_days - 1);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Calculate total budget.
     */
    private function calculateTotalBudget(): ?float
    {
        if (empty($this->budget_per_person) || empty($this->number_of_people)) {
            return null;
        }

        return round($this->budget_per_person * $this->number_of_people, 2);
    }

    /**
     * Get limit info for UI display.
     *
     * @return array<string, mixed>
     */
    private function getLimitInfo(): array
    {
        return $this->limitService->getLimitInfo(Auth::id());
    }

    /**
     * Get limit exceeded message.
     */
    private function getLimitExceededMessage(): string
    {
        $resetDate = $this->limitService->getResetDate()->translatedFormat('j F Y');

        return "Osiągnąłeś limit {$this->generationsLimit} generowań w tym miesiącu. "
            ."Limit odnowi się {$resetDate}. "
            .'Możesz nadal zapisywać szkice planów.';
    }

    // ==================== ERROR HANDLERS ====================

    /**
     * Handle save error.
     */
    private function handleSaveError(\Exception $e): void
    {
        Log::error('Failed to save travel plan', [
            'user_id' => Auth::id(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        $this->errorMessage = 'Wystąpił błąd podczas zapisywania planu. Spróbuj ponownie.';
    }

    /**
     * Handle generation error.
     */
    private function handleGenerationError(\Exception $e): void
    {
        // Rollback generation count
        $this->limitService->rollbackGeneration(Auth::id());

        Log::error('Failed to generate travel plan', [
            'user_id' => Auth::id(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        $this->errorMessage = 'Wystąpił problem z generowaniem planu. Spróbuj ponownie.';
    }

    /**
     * Handle limit error.
     */
    private function handleLimitError(LimitExceededException $e): void
    {
        $this->canGenerate = false;
        $this->errorMessage = $this->getLimitExceededMessage();

        Log::warning('Generation limit exceeded', [
            'user_id' => Auth::id(),
        ]);
    }

    // ==================== RENDER ====================

    /**
     * Render the component.
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.plans.create-plan-form', [
            'limitInfo' => $this->getLimitInfo(),
            'endDate' => $this->calculateEndDate(),
            'totalBudget' => $this->calculateTotalBudget(),
        ]);
    }
}
