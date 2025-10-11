<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\LimitExceededException;
use App\Models\AIGeneration;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Limit Service
 *
 * Manages monthly AI generation limits for users.
 * Provides protection against race conditions using pessimistic locking.
 */
class LimitService
{
    /**
     * Monthly generation limit per user.
     */
    public const MONTHLY_LIMIT = 10;

    /**
     * Get the number of generations used in the current month.
     */
    public function getGenerationCount(int $userId): int
    {
        return AIGeneration::forUser($userId)
            ->thisMonth()
            ->count();
    }

    /**
     * Check if user can generate a new plan (hasn't exceeded limit).
     */
    public function canGenerate(int $userId): bool
    {
        return $this->getGenerationCount($userId) < self::MONTHLY_LIMIT;
    }

    /**
     * Increment generation count with race condition protection.
     *
     * Uses pessimistic locking to prevent multiple concurrent requests
     * from exceeding the monthly limit.
     *
     * @throws LimitExceededException When monthly limit is exceeded
     */
    public function incrementGenerationCount(int $userId, ?int $travelPlanId = null): AIGeneration
    {
        return DB::transaction(function () use ($userId, $travelPlanId) {
            // Lock and count existing generations this month
            $count = AIGeneration::forUser($userId)
                ->thisMonth()
                ->lockForUpdate() // Pessimistic lock prevents race conditions
                ->count();

            if ($count >= self::MONTHLY_LIMIT) {
                throw new LimitExceededException(
                    'Monthly generation limit of '.self::MONTHLY_LIMIT.' exceeded'
                );
            }

            // Create new generation record
            return AIGeneration::create([
                'user_id' => $userId,
                'travel_plan_id' => $travelPlanId,
                'model_used' => config('ai.model', 'gpt-4o-mini'),
                'status' => 'pending',
            ]);
        });
    }

    /**
     * Rollback generation count (used when generation fails).
     *
     * Deletes the most recent pending generation for the user.
     * This allows the user to retry without consuming their limit.
     */
    public function rollbackGeneration(int $userId): void
    {
        AIGeneration::forUser($userId)
            ->thisMonth()
            ->pending()
            ->latest()
            ->first()
            ?->delete();
    }

    /**
     * Get the date when the limit will reset (first day of next month).
     */
    public function getResetDate(): Carbon
    {
        return now()->addMonth()->startOfMonth();
    }

    /**
     * Get remaining generations for the month.
     */
    public function getRemainingGenerations(int $userId): int
    {
        $used = $this->getGenerationCount($userId);

        return max(0, self::MONTHLY_LIMIT - $used);
    }

    /**
     * Get limit information for display.
     *
     * @return array<string, mixed>
     */
    public function getLimitInfo(int $userId): array
    {
        $used = $this->getGenerationCount($userId);
        $limit = self::MONTHLY_LIMIT;
        $remaining = $this->getRemainingGenerations($userId);
        $percentage = ($used / $limit) * 100;

        return [
            'used' => $used,
            'limit' => $limit,
            'remaining' => $remaining,
            'percentage' => round($percentage, 1),
            'can_generate' => $used < $limit,
            'reset_date' => $this->getResetDate()->format('Y-m-d'),
            'display_text' => "{$used}/{$limit} w tym miesiącu",
            'color' => match (true) {
                $percentage >= 90 => 'red',
                $percentage >= 70 => 'yellow',
                default => 'green',
            },
        ];
    }

    /**
     * Check if user has reached the limit.
     */
    public function hasReachedLimit(int $userId): bool
    {
        return $this->getGenerationCount($userId) >= self::MONTHLY_LIMIT;
    }

    /**
     * Get generations history for a user in current month.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, AIGeneration>
     */
    public function getMonthlyGenerations(int $userId)
    {
        return AIGeneration::forUser($userId)
            ->thisMonth()
            ->latest()
            ->get();
    }
}
