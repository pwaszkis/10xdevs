<?php

declare(strict_types=1);

namespace App\Livewire\Plans;

use App\Http\Requests\SubmitFeedbackRequest;
use App\Models\TravelPlan;
use App\Models\TravelPlanFeedback;
use Livewire\Attributes\Computed;
use Livewire\Component;

class FeedbackForm extends Component
{
    // 1. Public properties (state)
    public TravelPlan $plan;

    public bool $isExpanded = false;

    public ?bool $satisfied = null;

    /**
     * @var list<string>
     */
    public array $issues = [];

    public string $otherComment = '';

    public bool $isSubmitting = false;

    // 2. Computed properties

    /**
     * Check if plan already has feedback.
     */
    #[Computed]
    public function hasFeedback(): bool
    {
        return $this->plan->hasFeedback();
    }

    /**
     * Get existing feedback if available.
     */
    #[Computed]
    public function existingFeedback(): ?TravelPlanFeedback
    {
        return $this->plan->feedback;
    }

    /**
     * Get available issue types.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function availableIssues(): array
    {
        return [
            SubmitFeedbackRequest::ISSUE_NOT_ENOUGH_DETAILS => 'Za mało szczegółów',
            SubmitFeedbackRequest::ISSUE_NOT_MATCHING_PREFERENCES => 'Nie pasuje do moich preferencji',
            SubmitFeedbackRequest::ISSUE_POOR_ITINERARY_ORDER => 'Słaba kolejność zwiedzania',
            SubmitFeedbackRequest::ISSUE_OTHER => 'Inne',
        ];
    }

    // 3. Lifecycle hooks

    public function mount(TravelPlan $plan): void
    {
        $this->plan = $plan;

        // If feedback already exists, populate form
        if ($this->hasFeedback()) {
            $feedback = $this->existingFeedback();
            $this->satisfied = $feedback->satisfied;
            $this->issues = $feedback->issues ?? [];
        }
    }

    // 4. Actions

    /**
     * Toggle form visibility.
     */
    public function toggle(): void
    {
        $this->isExpanded = ! $this->isExpanded;
    }

    /**
     * Handle satisfied button click.
     */
    public function setSatisfied(bool $value): void
    {
        $this->satisfied = $value;

        // Clear issues if user is satisfied
        if ($value) {
            $this->issues = [];
            $this->otherComment = '';
        }
    }

    /**
     * Submit feedback.
     */
    public function submit(): void
    {
        // Prevent double submission
        if ($this->isSubmitting) {
            return;
        }

        $this->isSubmitting = true;

        try {
            // Validate
            $this->validate([
                'satisfied' => 'required|boolean',
                'issues' => 'array|required_if:satisfied,false',
                'issues.*' => [
                    'string',
                    'in:' . implode(',', array_keys($this->availableIssues())),
                ],
                'otherComment' => 'nullable|string|max:1000',
            ], [
                'satisfied.required' => 'Musisz wybrać czy plan spełnia Twoje oczekiwania.',
                'issues.required_if' => 'Wybierz przynajmniej jeden problem, jeśli plan nie spełnia oczekiwań.',
            ]);

            // Authorization: Component is only accessible from user's plan view,
            // so implicit authorization is already enforced. Additional check:
            if ($this->plan->user_id !== auth()->id()) {
                session()->flash('feedback-error', 'Nie masz uprawnień do tej operacji.');
                $this->isSubmitting = false;

                return;
            }

            // Prepare data
            $data = [
                'travel_plan_id' => $this->plan->id,
                'satisfied' => $this->satisfied,
                'issues' => $this->satisfied ? null : $this->prepareIssues(),
            ];

            // Create or update feedback
            TravelPlanFeedback::updateOrCreate(
                ['travel_plan_id' => $this->plan->id],
                $data
            );

            // Refresh plan relationship
            $this->plan->load('feedback');

            // Collapse form
            $this->isExpanded = false;

            // Dispatch success event
            $this->dispatch('feedback-submitted', [
                'planId' => $this->plan->id,
                'satisfied' => $this->satisfied,
            ]);

            // Show success message
            session()->flash('feedback-success', 'Dziękujemy za feedback!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-throw validation exceptions
            throw $e;
        } catch (\Exception $e) {
            // Log error
            \Log::error('Feedback submission failed', [
                'plan_id' => $this->plan->id,
                'error' => $e->getMessage(),
            ]);

            // Show error message
            session()->flash('feedback-error', 'Nie udało się zapisać feedbacku. Spróbuj ponownie.');
        } finally {
            $this->isSubmitting = false;
        }
    }

    /**
     * Prepare issues array with 'other' comment if applicable.
     *
     * @return list<string>
     */
    private function prepareIssues(): array
    {
        $issues = array_values(array_unique($this->issues));

        // If 'other' is selected and there's a comment, append it
        if (in_array(SubmitFeedbackRequest::ISSUE_OTHER, $issues, true) && $this->otherComment !== '') {
            $issues[] = 'other: ' . strip_tags($this->otherComment);
        }

        return $issues;
    }

    // 5. Render

    public function render(): \Illuminate\View\View
    {
        return view('livewire.plans.feedback-form');
    }
}
