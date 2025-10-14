<?php

declare(strict_types=1);

namespace App\Livewire\Profile;

use App\Actions\Preferences\UpdateUserPreferencesAction;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Update User Preferences Form Component
 *
 * Allows users to update their travel preferences.
 */
class UpdatePreferencesForm extends Component
{
    // Interest categories (multi-select)
    /** @var list<string> */
    public array $interestCategories = [];

    // Practical parameters
    public string $travelPace = '';

    public string $budgetLevel = '';

    public string $transportPreference = '';

    public string $restrictions = '';

    // Loading state
    public bool $isLoading = false;

    /**
     * Available interest categories
     *
     * @var array<string, string>
     */
    protected array $availableInterests = [
        'historia_kultura' => 'Historia i kultura',
        'przyroda_outdoor' => 'Przyroda i outdoor',
        'gastronomia' => 'Gastronomia',
        'nocne_zycie' => 'Nocne życie i rozrywka',
        'plaze_relaks' => 'Plaże i relaks',
        'sporty_aktywnosci' => 'Sporty i aktywności',
        'sztuka_muzea' => 'Sztuka i muzea',
    ];

    /**
     * Mount component - load current preferences
     */
    public function mount(): void
    {
        $user = Auth::user();

        if ($user && $user->preferences) {
            $this->interestCategories = $user->preferences->interests_categories ?? [];
            $this->travelPace = $user->preferences->travel_pace ?? '';
            $this->budgetLevel = $user->preferences->budget_level ?? '';
            $this->transportPreference = $user->preferences->transport_preference ?? '';
            $this->restrictions = $user->preferences->restrictions ?? '';
        }
    }

    /**
     * Validation rules
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'interestCategories' => ['required', 'array', 'min:1'],
            'interestCategories.*' => ['string', 'in:historia_kultura,przyroda_outdoor,gastronomia,nocne_zycie,plaze_relaks,sporty_aktywnosci,sztuka_muzea'],
            'travelPace' => ['required', 'string', 'in:spokojne,umiarkowane,intensywne'],
            'budgetLevel' => ['required', 'string', 'in:ekonomiczny,standardowy,premium'],
            'transportPreference' => ['required', 'string', 'in:pieszo_publiczny,wynajem_auta,mix'],
            'restrictions' => ['required', 'string', 'in:brak,dieta,mobilnosc'],
        ];
    }

    /**
     * Get validation messages
     *
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'interestCategories.required' => 'Wybierz co najmniej jedną kategorię zainteresowań.',
            'interestCategories.min' => 'Wybierz co najmniej jedną kategorię zainteresowań.',
            'travelPace.required' => 'Wybierz tempo podróżowania.',
            'budgetLevel.required' => 'Wybierz poziom budżetu.',
            'transportPreference.required' => 'Wybierz preferowany transport.',
            'restrictions.required' => 'Wybierz ograniczenia dietetyczne/dostępności.',
        ];
    }

    /**
     * Toggle interest category selection
     */
    public function toggleInterest(string $interest): void
    {
        if (in_array($interest, $this->interestCategories, true)) {
            $this->interestCategories = array_values(array_diff($this->interestCategories, [$interest]));
        } else {
            $this->interestCategories[] = $interest;
        }
    }

    /**
     * Check if interest is selected
     */
    public function isInterestSelected(string $interest): bool
    {
        return in_array($interest, $this->interestCategories, true);
    }

    /**
     * Set travel pace
     */
    public function setTravelPace(string $pace): void
    {
        $this->travelPace = $pace;
    }

    /**
     * Set budget level
     */
    public function setBudgetLevel(string $level): void
    {
        $this->budgetLevel = $level;
    }

    /**
     * Set transport preference
     */
    public function setTransportPreference(string $transport): void
    {
        $this->transportPreference = $transport;
    }

    /**
     * Set restrictions
     */
    public function setRestrictions(string $restrictions): void
    {
        $this->restrictions = $restrictions;
    }

    /**
     * Update user preferences
     */
    public function updatePreferences(UpdateUserPreferencesAction $action): void
    {
        $this->validate();

        $this->isLoading = true;

        try {
            $user = Auth::user();

            $action->execute($user, [
                'interests_categories' => $this->interestCategories,
                'travel_pace' => $this->travelPace,
                'budget_level' => $this->budgetLevel,
                'transport_preference' => $this->transportPreference,
                'restrictions' => $this->restrictions,
            ]);

            session()->flash('status', 'preferences-updated');

            $this->dispatch('preferences-updated');
        } catch (\Exception $e) {
            session()->flash('error', 'Wystąpił błąd podczas zapisywania preferencji.');
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Render component
     */
    public function render(): View
    {
        return view('livewire.profile.update-preferences-form', [
            'availableInterests' => $this->availableInterests,
        ]);
    }
}
