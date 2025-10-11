<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TravelPlan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Travel Service
 *
 * Handles CRUD operations for travel plans with transaction support.
 * Separates business logic from controllers and Livewire components.
 */
class TravelService
{
    /**
     * Create a new travel plan.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): TravelPlan
    {
        return DB::transaction(function () use ($data) {
            return TravelPlan::create($data);
        });
    }

    /**
     * Update an existing travel plan.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $travelId, array $data): TravelPlan
    {
        return DB::transaction(function () use ($travelId, $data) {
            /** @var TravelPlan $travel */
            $travel = TravelPlan::findOrFail($travelId);
            $travel->update($data);

            return $travel->fresh();
        });
    }

    /**
     * Delete a travel plan.
     */
    public function delete(int $travelId): bool
    {
        /** @var TravelPlan $travel */
        $travel = TravelPlan::findOrFail($travelId);

        return $travel->delete();
    }

    /**
     * Get travel plans for a specific user.
     *
     * @return Collection<int, TravelPlan>
     */
    public function getUserTravels(int $userId, ?string $status = null): Collection
    {
        $query = TravelPlan::forUser($userId)->latest();

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->get();
    }

    /**
     * Get a specific travel plan by ID.
     */
    public function find(int $travelId): ?TravelPlan
    {
        return TravelPlan::find($travelId);
    }

    /**
     * Get a specific travel plan by ID or fail.
     */
    public function findOrFail(int $travelId): TravelPlan
    {
        return TravelPlan::findOrFail($travelId);
    }

    /**
     * Get draft plans for a user.
     *
     * @return Collection<int, TravelPlan>
     */
    public function getUserDrafts(int $userId): Collection
    {
        return TravelPlan::forUser($userId)
            ->drafts()
            ->latest()
            ->get();
    }

    /**
     * Get planned (AI-generated) plans for a user.
     *
     * @return Collection<int, TravelPlan>
     */
    public function getUserPlannedTravels(int $userId): Collection
    {
        return TravelPlan::forUser($userId)
            ->planned()
            ->latest()
            ->get();
    }

    /**
     * Get completed plans for a user.
     *
     * @return Collection<int, TravelPlan>
     */
    public function getUserCompletedTravels(int $userId): Collection
    {
        return TravelPlan::forUser($userId)
            ->completed()
            ->latest()
            ->get();
    }

    /**
     * Update travel plan status.
     */
    public function updateStatus(int $travelId, string $status): TravelPlan
    {
        return $this->update($travelId, ['status' => $status]);
    }

    /**
     * Mark travel plan as planned (after AI generation).
     */
    public function markAsPlanned(int $travelId): TravelPlan
    {
        return $this->updateStatus($travelId, 'planned');
    }

    /**
     * Mark travel plan as completed.
     */
    public function markAsCompleted(int $travelId): TravelPlan
    {
        return $this->updateStatus($travelId, 'completed');
    }

    /**
     * Check if user owns the travel plan.
     */
    public function userOwnsPlan(int $userId, int $travelId): bool
    {
        return TravelPlan::where('id', $travelId)
            ->where('user_id', $userId)
            ->exists();
    }
}
