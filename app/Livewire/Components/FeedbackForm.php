<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Models\Feedback;
use Illuminate\Support\Facades\Http;
use Livewire\Attributes\Prop;
use Livewire\Component;

class FeedbackForm extends Component
{
    #[Prop]
    public int $travelPlanId;

    #[Prop]
    public ?Feedback $existingFeedback = null;

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

        $response = Http::post("/api/travel-plans/{$this->travelPlanId}/feedback", [
            'satisfied' => $this->satisfied,
            'issues' => $this->satisfied ? null : $this->issues,
            'other_comment' => $this->otherComment,
        ]);

        $this->isSubmitting = false;

        if ($response->status() === 400) {
            // Feedback already exists
            session()->flash('error', 'Feedback dla tego planu został już przesłany.');
            $this->showForm = false;

            return;
        }

        if ($response->successful()) {
            session()->flash('success', 'Dziękujemy za feedback!');
            $this->dispatch('feedback-submitted', feedback: $response->json('data'));
            $this->resetForm();
            $this->existingFeedback = Feedback::make($response->json('data'));
        } else {
            session()->flash('error', 'Nie udało się przesłać feedbacku. Spróbuj ponownie.');
        }
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
            default => $issue,
        };
    }

    /**
     * Render the component.
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.components.feedback-form');
    }
}
