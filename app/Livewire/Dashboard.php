<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\TravelPlan;
use App\Services\LimitService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Dashboard Component
 *
 * Main dashboard view displaying user's travel plans with filtering capabilities.
 * Shows hero section, quick filters, and paginated travel plan cards.
 */
class Dashboard extends Component
{
    use WithPagination;

    // ==================== COMPONENT STATE ====================

    /** Current active filter (all, draft, planned, completed) */
    public string $statusFilter = 'all';

    /** Search query */
    public ?string $search = null;

    /** Sort by field */
    public string $sortBy = 'created_at';

    /** Sort direction */
    public string $sortDirection = 'desc';

    // ==================== LIFECYCLE HOOKS ====================

    /**
     * Mount component - initialize state.
     */
    public function mount(): void
    {
        // Initialize with default filter
        $this->statusFilter = 'all';
    }

    /**
     * Updated hook - reset pagination when filters change.
     */
    public function updated(string $property): void
    {
        if (in_array($property, ['statusFilter', 'search'])) {
            $this->resetPage();
        }
    }

    // ==================== PUBLIC ACTIONS ====================

    /**
     * Set status filter.
     */
    public function setFilter(string $status): void
    {
        $this->statusFilter = $status;
        $this->resetPage();
    }

    /**
     * Clear all filters.
     */
    public function clearFilters(): void
    {
        $this->statusFilter = 'all';
        $this->search = null;
        $this->resetPage();
    }

    /**
     * Set sort field and direction.
     */
    public function setSortBy(string $field): void
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'desc';
        }
        $this->resetPage();
    }

    // ==================== COMPUTED PROPERTIES ====================

    /**
     * Get filtered and paginated travel plans.
     *
     * @return LengthAwarePaginator<int, TravelPlan>
     */
    #[Computed]
    public function plans(): LengthAwarePaginator
    {
        $query = TravelPlan::query()
            ->where('user_id', Auth::id());

        // Apply status filter
        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                    ->orWhere('destination', 'like', '%' . $this->search . '%');
            });
        }

        // Apply sorting
        $query->orderBy($this->sortBy, $this->sortDirection);

        return $query->paginate(20);
    }

    /**
     * Get count of plans by status for filter badges.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function planCounts(): array
    {
        $userId = Auth::id();

        return [
            'all' => TravelPlan::where('user_id', $userId)->count(),
            'draft' => TravelPlan::where('user_id', $userId)->where('status', 'draft')->count(),
            'planned' => TravelPlan::where('user_id', $userId)->where('status', 'planned')->count(),
            'completed' => TravelPlan::where('user_id', $userId)->where('status', 'completed')->count(),
        ];
    }

    /**
     * Check if user has any plans.
     */
    #[Computed]
    public function hasPlans(): bool
    {
        return TravelPlan::where('user_id', Auth::id())->exists();
    }

    /**
     * Get user's nickname for personalized greeting.
     */
    #[Computed]
    public function userNickname(): string
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return $user->nickname ?? $user->name ?? 'Podróżniku';
    }

    /**
     * Get AI generation limit info for display.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function aiLimitInfo(): array
    {
        $limitService = app(LimitService::class);

        return $limitService->getLimitInfo(Auth::id());
    }

    // ==================== RENDER ====================

    /**
     * Render the component.
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.dashboard');
    }
}
