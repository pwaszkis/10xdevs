<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Feedback Model
 *
 * @property int $id
 * @property int $travel_plan_id
 * @property bool $satisfied
 * @property list<string>|null $issues
 * @property string|null $other_comment
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Feedback extends Model
{
    /** @use HasFactory<\Database\Factories\FeedbackFactory> */
    use HasFactory;

    protected $table = 'feedback';

    protected $fillable = [
        'travel_plan_id',
        'satisfied',
        'issues',
        'other_comment',
    ];

    protected $casts = [
        'satisfied' => 'boolean',
        'issues' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the travel plan that owns the feedback.
     *
     * @return BelongsTo<TravelPlan>
     */
    public function travelPlan(): BelongsTo
    {
        return $this->belongsTo(TravelPlan::class);
    }
}
