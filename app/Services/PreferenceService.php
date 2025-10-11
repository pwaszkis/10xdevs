<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\UserPreference;

/**
 * Preference Service
 *
 * Handles retrieval and management of user travel preferences.
 * Used to personalize AI-generated travel plans.
 */
class PreferenceService
{
    /**
     * Get user preferences for AI generation.
     *
     * Returns normalized preference data ready for AI prompt building.
     *
     * @return array<string, mixed>
     */
    public function getUserPreferences(int $userId): array
    {
        /** @var User $user */
        $user = User::findOrFail($userId);
        $preferences = $user->preferences;

        // Return default preferences if none set
        if ($preferences === null) {
            return $this->getDefaultPreferences();
        }

        return [
            'interests' => $preferences->interests_categories ?? [],
            'travel_pace' => $preferences->travel_pace ?? 'moderate',
            'budget_level' => $preferences->budget_level ?? 'standard',
            'transport_preference' => $preferences->transport_preference ?? 'mixed',
            'restrictions' => $this->parseRestrictions($preferences->restrictions),
        ];
    }

    /**
     * Get default preferences for users without saved preferences.
     *
     * @return array<string, mixed>
     */
    public function getDefaultPreferences(): array
    {
        return [
            'interests' => [],
            'travel_pace' => 'moderate',
            'budget_level' => 'standard',
            'transport_preference' => 'mixed',
            'restrictions' => [],
        ];
    }

    /**
     * Parse restrictions string into array.
     *
     * @return list<string>
     */
    private function parseRestrictions(?string $restrictions): array
    {
        if ($restrictions === null || trim($restrictions) === '') {
            return [];
        }

        // Split by comma and trim whitespace
        return array_values(
            array_filter(
                array_map('trim', explode(',', $restrictions)),
                fn ($item) => $item !== ''
            )
        );
    }

    /**
     * Check if user has set preferences.
     */
    public function hasPreferences(int $userId): bool
    {
        return UserPreference::where('user_id', $userId)->exists();
    }

    /**
     * Get or create user preferences.
     */
    public function getOrCreatePreferences(int $userId): UserPreference
    {
        return UserPreference::firstOrCreate(
            ['user_id' => $userId],
            $this->getDefaultPreferencesForModel()
        );
    }

    /**
     * Get default preferences for model creation.
     *
     * @return array<string, mixed>
     */
    private function getDefaultPreferencesForModel(): array
    {
        return [
            'language' => config('app.locale', 'pl'),
            'timezone' => config('app.timezone', 'Europe/Warsaw'),
            'currency' => 'PLN',
            'notifications_enabled' => true,
            'email_notifications' => true,
            'push_notifications' => false,
            'theme' => 'light',
            'interests_categories' => [],
            'travel_pace' => 'moderate',
            'budget_level' => 'standard',
            'transport_preference' => 'mixed',
            'restrictions' => null,
        ];
    }

    /**
     * Update user preferences.
     *
     * @param  array<string, mixed>  $data
     */
    public function updatePreferences(int $userId, array $data): UserPreference
    {
        $preferences = $this->getOrCreatePreferences($userId);
        $preferences->update($data);

        return $preferences->fresh();
    }

    /**
     * Get available travel pace options.
     *
     * @return array<string, string>
     */
    public function getTravelPaceOptions(): array
    {
        return [
            'relaxed' => 'Relaxed - Few activities, plenty of rest time',
            'moderate' => 'Moderate - Balanced pace with leisure time',
            'active' => 'Active - Packed schedule, many activities',
        ];
    }

    /**
     * Get available budget level options.
     *
     * @return array<string, string>
     */
    public function getBudgetLevelOptions(): array
    {
        return [
            'budget' => 'Budget - Affordable options, focus on value',
            'standard' => 'Standard - Mid-range options',
            'premium' => 'Premium - High-end experiences',
            'luxury' => 'Luxury - Top-tier accommodations and activities',
        ];
    }

    /**
     * Get available transport preference options.
     *
     * @return array<string, string>
     */
    public function getTransportPreferenceOptions(): array
    {
        return [
            'public' => 'Public Transport - Buses, trains, metro',
            'mixed' => 'Mixed - Combination of public and private',
            'private' => 'Private - Taxis, car rentals',
            'walking' => 'Walking - Explore on foot when possible',
        ];
    }

    /**
     * Get available interest categories.
     *
     * @return array<string, string>
     */
    public function getInterestCategories(): array
    {
        return [
            'history_culture' => 'History & Culture',
            'nature_outdoor' => 'Nature & Outdoor Activities',
            'gastronomy' => 'Food & Gastronomy',
            'nightlife' => 'Nightlife & Entertainment',
            'beaches' => 'Beaches & Relaxation',
            'sports' => 'Sports & Adventure',
            'art_museums' => 'Art & Museums',
            'shopping' => 'Shopping',
            'photography' => 'Photography',
            'local_life' => 'Local Life & Authentic Experiences',
        ];
    }
}
