<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Travel Plan Model
 *
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $destination
 * @property float|null $destination_lat
 * @property float|null $destination_lng
 * @property \Illuminate\Support\Carbon $departure_date
 * @property int $number_of_days
 * @property int $number_of_people
 * @property float|null $budget_per_person
 * @property string|null $budget_currency
 * @property string|null $user_notes
 * @property string $status
 * @property bool $has_ai_plan
 * @property int $pdf_exports_count
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class TravelPlan extends Model
{
    /** @use HasFactory<\Database\Factories\TravelPlanFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'destination',
        'destination_lat',
        'destination_lng',
        'departure_date',
        'number_of_days',
        'number_of_people',
        'budget_per_person',
        'budget_currency',
        'user_notes',
        'status',
    ];

    protected $casts = [
        'destination_lat' => 'float',
        'destination_lng' => 'float',
        'number_of_days' => 'integer',
        'number_of_people' => 'integer',
        'budget_per_person' => 'float',
        'pdf_exports_count' => 'integer',
        'departure_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'draft',
        'budget_currency' => 'PLN',
    ];

    /**
     * Get the user that owns the travel plan.
     *
     * @return BelongsTo<User>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the days for the travel plan.
     *
     * @return HasMany<PlanDay>
     */
    public function days(): HasMany
    {
        return $this->hasMany(PlanDay::class)->orderBy('day_number');
    }

    /**
     * Get the feedback for the travel plan.
     *
     * @return HasOne<TravelPlanFeedback>
     */
    public function feedback(): HasOne
    {
        return $this->hasOne(TravelPlanFeedback::class);
    }

    /**
     * Check if plan has feedback.
     */
    public function hasFeedback(): bool
    {
        return $this->feedback()->exists();
    }

    /**
     * Get the AI recommendations for the travel plan.
     *
     * @return HasMany<AIRecommendation>
     */
    public function aiRecommendations(): HasMany
    {
        return $this->hasMany(AIRecommendation::class);
    }

    /**
     * Get the activities for the travel plan.
     *
     * @return HasMany<Activity>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * Get the AI generations for the travel plan.
     *
     * @return HasMany<AIGeneration>
     */
    public function aiGenerations(): HasMany
    {
        return $this->hasMany(AIGeneration::class);
    }

    /**
     * Scope a query to only include plans owned by the authenticated user.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<TravelPlan>  $query
     * @return \Illuminate\Database\Eloquent\Builder<TravelPlan>
     */
    public function scopeOwnedBy($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include plans for a specific user.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<TravelPlan>  $query
     * @return \Illuminate\Database\Eloquent\Builder<TravelPlan>
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include draft plans.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<TravelPlan>  $query
     * @return \Illuminate\Database\Eloquent\Builder<TravelPlan>
     */
    public function scopeDrafts($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope a query to only include planned plans.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<TravelPlan>  $query
     * @return \Illuminate\Database\Eloquent\Builder<TravelPlan>
     */
    public function scopePlanned($query)
    {
        return $query->where('status', 'planned');
    }

    /**
     * Scope a query to only include completed plans.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<TravelPlan>  $query
     * @return \Illuminate\Database\Eloquent\Builder<TravelPlan>
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to filter by status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<TravelPlan>  $query
     * @return \Illuminate\Database\Eloquent\Builder<TravelPlan>
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Check if plan is a draft.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if plan is planned.
     */
    public function isPlanned(): bool
    {
        return $this->status === 'planned';
    }

    /**
     * Check if plan is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if plan has AI-generated content.
     * Determined by whether plan has days.
     */
    public function getHasAiPlanAttribute(): bool
    {
        return $this->days()->exists();
    }

    /**
     * Get the start date (alias for departure_date).
     */
    public function getStartDateAttribute(): ?\Illuminate\Support\Carbon
    {
        return $this->departure_date;
    }

    /**
     * Get the calculated end date based on departure_date and number_of_days.
     */
    public function getEndDateAttribute(): ?\Illuminate\Support\Carbon
    {
        if ($this->departure_date === null || $this->number_of_days === null) {
            return null;
        }

        return $this->departure_date->copy()->addDays($this->number_of_days - 1);
    }

    /**
     * Get the total budget for all people.
     */
    public function getTotalBudgetAttribute(): ?float
    {
        if ($this->budget_per_person === null || $this->number_of_people === null) {
            return null;
        }

        return round($this->budget_per_person * $this->number_of_people, 2);
    }
}
