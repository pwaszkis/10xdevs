<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Http\Requests\SubmitFeedbackRequest;
use App\Models\TravelPlan;
use App\Models\TravelPlanFeedback;
use Livewire\Attributes\Prop;
use Livewire\Component;

class FeedbackForm extends Component
{
    #[Prop]
    public int $travelPlanId;

    #[Prop]
    public ?TravelPlanFeedback $existingFeedback = null;

    // Form state
    public bool $showForm = false;

    public ?bool $satisfied = null;

    /**
     * @var array<int, string>
     */
    public array $issues = [];

    public ?string $otherComment = null;

    public bool $isSubmitting = false;

    /**
     * Check if feedback can be submitted.
     */
    public function canSubmitFeedback(): bool
    {
        return $this->existingFeedback === null;
    }

    /**
     * Toggle form visibility.
     */
    public function toggleForm(): void
    {
        $this->showForm = ! $this->showForm;

        if (! $this->showForm) {
            $this->resetForm();
        }
    }

    /**
     * Submit feedback.
     */
    public function submitFeedback(): void
    {
        $this->validate();

        $this->isSubmitting = true;

        try {
            // Get the travel plan
            $plan = TravelPlan::findOrFail($this->travelPlanId);

            // Check authorization: plan belongs to authenticated user
            if ($plan->user_id !== auth()->id()) {
                session()->flash('error', 'Nie masz uprawnień do tej operacji.');
                $this->isSubmitting = false;

                return;
            }

            // Prepare data
            $data = [
                'travel_plan_id' => $this->travelPlanId,
                'satisfied' => $this->satisfied,
                'issues' => $this->satisfied ? null : $this->prepareIssues(),
            ];

            // Create or update feedback
            $feedback = TravelPlanFeedback::updateOrCreate(
                ['travel_plan_id' => $this->travelPlanId],
                $data
            );

            $this->isSubmitting = false;

            session()->flash('success', 'Dziękujemy za feedback!');
            $this->dispatch('feedback-submitted', feedback: $feedback->toArray());
            $this->resetForm();
            $this->existingFeedback = $feedback;
        } catch (\Exception $e) {
            $this->isSubmitting = false;
            \Log::error('Feedback submission failed', [
                'travel_plan_id' => $this->travelPlanId,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Nie udało się przesłać feedbacku. Spróbuj ponownie.');
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

        // Map old issue keys to new ones
        $issueMap = [
            'za_malo_szczegolow' => SubmitFeedbackRequest::ISSUE_NOT_ENOUGH_DETAILS,
            'nie_pasuje_do_preferencji' => SubmitFeedbackRequest::ISSUE_NOT_MATCHING_PREFERENCES,
            'slaba_kolejnosc' => SubmitFeedbackRequest::ISSUE_POOR_ITINERARY_ORDER,
            'inne' => SubmitFeedbackRequest::ISSUE_OTHER,
        ];

        $mappedIssues = array_map(fn ($issue) => $issueMap[$issue] ?? $issue, $issues);

        // If 'other' is selected and there's a comment, append it
        if (in_array(SubmitFeedbackRequest::ISSUE_OTHER, $mappedIssues, true) && $this->otherComment) {
            $mappedIssues[] = 'other: '.strip_tags($this->otherComment);
        }

        return $mappedIssues;
    }

    /**
     * Reset form state.
     */
    protected function resetForm(): void
    {
        $this->satisfied = null;
        $this->issues = [];
        $this->otherComment = null;
        $this->showForm = false;
    }

    /**
     * Validation rules.
     *
     * @return array<string, string>
     */
    protected function rules(): array
    {
        return [
            'satisfied' => 'required|boolean',
            'issues' => 'required_if:satisfied,false|array|max:4',
            'issues.*' => 'in:za_malo_szczegolow,nie_pasuje_do_preferencji,slaba_kolejnosc,inne',
            'otherComment' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'satisfied.required' => 'Wybierz odpowiedź Tak lub Nie.',
            'issues.required_if' => 'Zaznacz przynajmniej jeden problem.',
            'issues.max' => 'Możesz wybrać maksymalnie 4 problemy.',
            'otherComment.max' => 'Komentarz może mieć maksymalnie 1000 znaków.',
        ];
    }

    /**
     * Get issue label.
     */
    public function getIssueLabel(string $issue): string
    {
        return match ($issue) {
            'za_malo_szczegolow' => 'Za mało szczegółów',
            'nie_pasuje_do_preferencji' => 'Nie pasuje do moich preferencji',
            'slaba_kolejnosc' => 'Słaba kolejność zwiedzania',
            'inne' => 'Inne',
            SubmitFeedbackRequest::ISSUE_NOT_ENOUGH_DETAILS => 'Za mało szczegółów',
            SubmitFeedbackRequest::ISSUE_NOT_MATCHING_PREFERENCES => 'Nie pasuje do moich preferencji',
            SubmitFeedbackRequest::ISSUE_POOR_ITINERARY_ORDER => 'Słaba kolejność zwiedzania',
            SubmitFeedbackRequest::ISSUE_OTHER => 'Inne',
            default => $issue,
        };
    }

    /**
     * Get available issue options.
     *
     * @return array<string, string>
     */
    public function getAvailableIssues(): array
    {
        return [
            'za_malo_szczegolow' => 'Za mało szczegółów',
            'nie_pasuje_do_preferencji' => 'Nie pasuje do moich preferencji',
            'slaba_kolejnosc' => 'Słaba kolejność zwiedzania',
            'inne' => 'Inne',
        ];
    }

    /**
     * Render the component.
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.components.feedback-form');
    }
}
