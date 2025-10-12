<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Welcome Component
 *
 * Post-onboarding welcome screen shown after user completes onboarding.
 */
class Welcome extends Component
{
    /**
     * Mount component
     */
    public function mount(): void
    {
        // Redirect if onboarding not completed
        if (! Auth::check() || ! Auth::user()->hasCompletedOnboarding()) {
            redirect()->route('onboarding');
        }
    }

    /**
     * Redirect to dashboard
     */
    public function goToDashboard(): void
    {
        redirect()->route('dashboard');
    }

    /**
     * Redirect to create plan
     */
    public function createFirstPlan(): void
    {
        redirect()->route('plans.create');
    }

    /**
     * Render component
     */
    public function render(): View
    {
        $user = Auth::user();

        return view('livewire.welcome', [
            'displayName' => $user->display_name ?? $user->name,
        ])->layout('layouts.app');
    }
}
