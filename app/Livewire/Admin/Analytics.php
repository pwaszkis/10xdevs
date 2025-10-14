<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Services\AnalyticsService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Analytics extends Component
{
    public function render(AnalyticsService $analytics): View
    {
        return view('livewire.admin.analytics', [
            'onboarding' => $analytics->getOnboardingCompletionRate(),
            'engagement' => $analytics->getUserEngagementMetrics(),
            'events' => $analytics->getEventDistribution(),
        ]);
    }
}
