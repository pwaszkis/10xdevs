<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Services\LimitService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class LimitCounter extends Component
{
    public int $used = 0;

    public int $limit = 10;

    public string $colorClass = 'text-green-600';

    public function mount(LimitService $limitService): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $limitInfo = $limitService->getLimitInfo($user->id);

        $this->used = $limitInfo['used'];
        $this->limit = $limitInfo['limit'];
        $this->colorClass = $this->getColorClass($limitInfo['percentage']);
    }

    protected function getColorClass(float $percentage): string
    {
        return match (true) {
            $percentage >= 90 => 'text-red-600 dark:text-red-400',
            $percentage >= 70 => 'text-yellow-600 dark:text-yellow-400',
            default => 'text-green-600 dark:text-green-400',
        };
    }

    public function render(): View
    {
        return view('livewire.components.limit-counter', [
            'resetDate' => now()->addMonth()->startOfMonth()->format('j.m.Y'),
        ]);
    }
}
