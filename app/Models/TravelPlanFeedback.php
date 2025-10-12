<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TravelPlanFeedback extends Model
{
    /** @use HasFactory<\Database\Factories\TravelPlanFeedbackFactory> */
    use HasFactory;

    // 1. Table configuration
    protected $table = 'travel_plan_feedback';

    // 2. Mass assignment
    /**
     * @var list<string>
     */
    protected $fillable = [
        'travel_plan_id',
        'satisfied',
        'issues',
    ];

    // 3. Casting
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'satisfied' => 'boolean',
            'issues' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // 4. Relationships

    /**
     * @return BelongsTo<TravelPlan, TravelPlanFeedback>
     */
    public function travelPlan(): BelongsTo
    {
        return $this->belongsTo(TravelPlan::class);
    }

    // 5. Business logic methods

    /**
     * Check if feedback is positive.
     */
    public function isPositive(): bool
    {
        return $this->satisfied === true;
    }

    /**
     * Check if feedback has specific issue.
     */
    public function hasIssue(string $issueType): bool
    {
        if ($this->satisfied) {
            return false;
        }

        return in_array($issueType, $this->issues ?? [], true);
    }

    /**
     * Get formatted issues list.
     *
     * @return list<string>
     */
    public function getFormattedIssues(): array
    {
        if ($this->satisfied || ! is_array($this->issues)) {
            return [];
        }

        return array_values(array_map(
            fn (string $issue): string => $this->translateIssue($issue),
            $this->issues
        ));
    }

    /**
     * Translate issue key to human-readable text.
     */
    private function translateIssue(string $issue): string
    {
        return match ($issue) {
            'not_enough_details' => 'Za mało szczegółów',
            'not_matching_preferences' => 'Nie pasuje do moich preferencji',
            'poor_itinerary_order' => 'Słaba kolejność zwiedzania',
            'other' => 'Inne problemy',
            default => $issue,
        };
    }
}
