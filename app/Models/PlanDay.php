<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Plan Day Model
 *
 * @property int $id
 * @property int $travel_plan_id
 * @property int $day_number
 * @property \Illuminate\Support\Carbon $date
 * @property string|null $summary
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class PlanDay extends Model
{
    /** @use HasFactory<\Database\Factories\PlanDayFactory> */
    use HasFactory;

    protected $fillable = [
        'travel_plan_id',
        'day_number',
        'date',
        'summary',
    ];

    protected $casts = [
        'day_number' => 'integer',
        'date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the travel plan that owns the day.
     *
     * @return BelongsTo<TravelPlan>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(TravelPlan::class, 'travel_plan_id');
    }

    /**
     * Get the points for the day.
     *
     * @return HasMany<PlanPoint>
     */
    public function points(): HasMany
    {
        return $this->hasMany(PlanPoint::class)->orderBy('order_number');
    }
}
