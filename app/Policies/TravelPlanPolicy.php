<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TravelPlan;
use App\Models\User;

/**
 * Travel Plan Policy
 *
 * Defines authorization rules for TravelPlan model.
 * Ensures users can only access their own plans.
 */
class TravelPlanPolicy
{
    /**
     * Determine if the user can view any travel plans.
     *
     * This is used for index pages - users can view their own plans list.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can see their own plans list
    }

    /**
     * Determine if the user can view the travel plan.
     *
     * Users can only view their own plans.
     */
    public function view(User $user, TravelPlan $travelPlan): bool
    {
        return $user->id === $travelPlan->user_id;
    }

    /**
     * Determine if the user can create travel plans.
     *
     * All authenticated users can create plans.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can update the travel plan.
     *
     * Users can only update their own plans.
     */
    public function update(User $user, TravelPlan $travelPlan): bool
    {
        return $user->id === $travelPlan->user_id;
    }

    /**
     * Determine if the user can delete the travel plan.
     *
     * Users can only delete their own plans.
     */
    public function delete(User $user, TravelPlan $travelPlan): bool
    {
        return $user->id === $travelPlan->user_id;
    }

    /**
     * Determine if the user can restore the travel plan.
     *
     * Users can only restore their own soft-deleted plans.
     */
    public function restore(User $user, TravelPlan $travelPlan): bool
    {
        return $user->id === $travelPlan->user_id;
    }

    /**
     * Determine if the user can permanently delete the travel plan.
     *
     * Users can only force delete their own plans.
     */
    public function forceDelete(User $user, TravelPlan $travelPlan): bool
    {
        return $user->id === $travelPlan->user_id;
    }

    /**
     * Determine if the user can generate AI plan for this travel plan.
     *
     * Users can only generate plans for their own travel plans.
     */
    public function generate(User $user, TravelPlan $travelPlan): bool
    {
        return $user->id === $travelPlan->user_id;
    }

    /**
     * Determine if the user can regenerate AI plan for this travel plan.
     *
     * Users can regenerate their own planned travel plans.
     */
    public function regenerate(User $user, TravelPlan $travelPlan): bool
    {
        return $user->id === $travelPlan->user_id
            && $travelPlan->status === 'planned';
    }
}
