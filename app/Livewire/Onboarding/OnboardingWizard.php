<?php

declare(strict_types=1);

namespace App\Livewire\Onboarding;

use App\Actions\Onboarding\CompleteOnboardingAction;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Onboarding Wizard Component
 *
 * Manages the multi-step onboarding process for new users.
 * Steps:
 * 1. Basic information (nickname, home_location)
 * 2. Interest categories (multi-select)
 * 3. Practical parameters (travel_pace, budget_level, transport, restrictions)
 * 4. Summary and completion
 */
class OnboardingWizard extends Component
{
    // Current step (1-4)
    public int $currentStep = 1;

    // Step 1: Basic information
    public string $nickname = '';

    public string $homeLocation = '';

    // Step 2: Interest categories (multi-select)
    /** @var list<string> */
    public array $interestCategories = [];

    // Step 3: Practical parameters
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
     * Mount component
     */
    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();

        // Redirect if onboarding already completed
        if ($user->hasCompletedOnboarding()) {
            redirect()->route('dashboard');
        }

        // Load existing data if user partially completed onboarding
        if ($user->onboarding_step > 1) {
            $this->currentStep = $user->onboarding_step;
            $this->loadExistingData($user);
        }
    }

    /**
     * Load existing data from user model
     */
    protected function loadExistingData(User $user): void
    {
        $this->nickname = $user->nickname ?? '';
        $this->homeLocation = $user->home_location ?? '';

        if ($user->preferences) {
            $this->interestCategories = $user->preferences->interests_categories ?? [];
            $this->travelPace = $user->preferences->travel_pace ?? '';
            $this->budgetLevel = $user->preferences->budget_level ?? '';
            $this->transportPreference = $user->preferences->transport_preference ?? '';
            $this->restrictions = $user->preferences->restrictions ?? '';
        }
    }

    /**
     * Validation rules for each step
     *
     * @return array<string, mixed>
     */
    protected function rulesForStep(int $step): array
    {
        return match ($step) {
            1 => [
                'nickname' => 'required|string|max:50',
                'homeLocation' => 'required|string|max:100',
            ],
            2 => [
                'interestCategories' => 'required|array|min:1',
                'interestCategories.*' => 'string|in:'.implode(',', array_keys($this->availableInterests)),
            ],
            3 => [
                'travelPace' => 'required|in:spokojne,umiarkowane,intensywne',
                'budgetLevel' => 'required|in:ekonomiczny,standardowy,premium',
                'transportPreference' => 'required|in:pieszo_publiczny,wynajem_auta,mix',
                'restrictions' => 'required|in:brak,dieta,mobilnosc',
            ],
            default => [],
        };
    }

    /**
     * Custom validation messages
     *
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'nickname.required' => 'Nickname jest wymagany.',
            'nickname.max' => 'Nickname może mieć maksymalnie 50 znaków.',
            'homeLocation.required' => 'Lokalizacja domowa jest wymagana.',
            'homeLocation.max' => 'Lokalizacja może mieć maksymalnie 100 znaków.',
            'interestCategories.required' => 'Wybierz przynajmniej jedną kategorię zainteresowań.',
            'interestCategories.min' => 'Wybierz przynajmniej jedną kategorię zainteresowań.',
            'travelPace.required' => 'Wybierz tempo podróży.',
            'budgetLevel.required' => 'Wybierz poziom budżetu.',
            'transportPreference.required' => 'Wybierz preferowany transport.',
            'restrictions.required' => 'Wybierz ograniczenia (lub "brak").',
        ];
    }

    /**
     * Go to next step
     */
    public function nextStep(): void
    {
        $this->validate($this->rulesForStep($this->currentStep), $this->messages());

        $this->saveStepData();

        if ($this->currentStep < 4) {
            $this->currentStep++;
        }
    }

    /**
     * Go to previous step
     */
    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    /**
     * Save current step data to database
     */
    protected function saveStepData(): void
    {
        /** @var User $user */
        $user = Auth::user();

        DB::transaction(function () use ($user) {
            // Update user data (step 1)
            if ($this->currentStep === 1) {
                $user->update([
                    'nickname' => $this->nickname,
                    'home_location' => $this->homeLocation,
                    'onboarding_step' => 2,
                ]);
            }

            // Update preferences (steps 2-3)
            if ($this->currentStep === 2 || $this->currentStep === 3) {
                $preferences = $user->preferences ?? new UserPreference;

                if ($this->currentStep === 2) {
                    $preferences->interests_categories = $this->interestCategories;
                }

                if ($this->currentStep === 3) {
                    $preferences->travel_pace = $this->travelPace;
                    $preferences->budget_level = $this->budgetLevel;
                    $preferences->transport_preference = $this->transportPreference;
                    $preferences->restrictions = $this->restrictions;
                }

                if (! $preferences->exists) {
                    $user->preferences()->save($preferences);
                } else {
                    $preferences->save();
                }

                $user->update([
                    'onboarding_step' => $this->currentStep + 1,
                ]);
            }
        });
    }

    /**
     * Complete onboarding
     */
    public function completeOnboarding(): void
    {
        $this->validate($this->rulesForStep(3), $this->messages());

        $this->isLoading = true;

        try {
            /** @var User $user */
            $user = Auth::user();

            // Save final step data
            $this->saveStepData();

            // Mark onboarding as completed
            $completeAction = new CompleteOnboardingAction;
            $completeAction->execute($user);

            // Redirect to welcome screen
            session()->flash('success', 'Profil został pomyślnie skonfigurowany!');
            redirect()->route('welcome');
        } catch (\Exception $e) {
            $this->isLoading = false;
            session()->flash('error', 'Wystąpił błąd podczas zapisywania danych. Spróbuj ponownie.');
        }
    }

    /**
     * Toggle interest category selection
     */
    public function toggleInterest(string $category): void
    {
        if (in_array($category, $this->interestCategories, true)) {
            $this->interestCategories = array_values(
                array_filter($this->interestCategories, fn ($c) => $c !== $category)
            );
        } else {
            $this->interestCategories[] = $category;
        }
    }

    /**
     * Check if interest is selected
     */
    public function isInterestSelected(string $category): bool
    {
        return in_array($category, $this->interestCategories, true);
    }

    /**
     * Get available interests
     *
     * @return array<string, string>
     */
    public function getAvailableInterestsProperty(): array
    {
        return $this->availableInterests;
    }

    /**
     * Check if can proceed to next step
     */
    public function getCanProceedProperty(): bool
    {
        return match ($this->currentStep) {
            1 => ! empty($this->nickname) && ! empty($this->homeLocation),
            2 => count($this->interestCategories) > 0,
            3 => ! empty($this->travelPace) && ! empty($this->budgetLevel)
                && ! empty($this->transportPreference) && ! empty($this->restrictions),
            default => false,
        };
    }

    /**
     * Render component
     */
    public function render(): View
    {
        return view('livewire.onboarding.onboarding-wizard')
            ->layout('layouts.onboarding');
    }
}
