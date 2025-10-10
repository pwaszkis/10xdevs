<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $travel_plan_id
 * @property string $type
 * @property string $content
 * @property bool $is_accepted
 * @property array<string, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read TravelPlan $travelPlan
 */
class AIRecommendation extends Model
{
    /** @use HasFactory<\Database\Factories\AIRecommendationFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'travel_plan_id',
        'type',
        'content',
        'is_accepted',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_accepted' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the travel plan that owns the recommendation
     *
     * @return BelongsTo<TravelPlan>
     */
    public function travelPlan(): BelongsTo
    {
        return $this->belongsTo(TravelPlan::class);
    }
}
