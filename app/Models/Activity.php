<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $travel_plan_id
 * @property string $name
 * @property string|null $description
 * @property string $type
 * @property \Illuminate\Support\Carbon $date
 * @property string|null $start_time
 * @property string|null $end_time
 * @property string|null $location
 * @property float|null $latitude
 * @property float|null $longitude
 * @property float|null $cost
 * @property string|null $currency
 * @property string|null $booking_reference
 * @property string|null $notes
 * @property int $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read TravelPlan $travelPlan
 */
class Activity extends Model
{
    /** @use HasFactory<\Database\Factories\ActivityFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'travel_plan_id',
        'name',
        'description',
        'type',
        'date',
        'start_time',
        'end_time',
        'location',
        'latitude',
        'longitude',
        'cost',
        'currency',
        'booking_reference',
        'notes',
        'order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'latitude' => 'float',
        'longitude' => 'float',
        'cost' => 'float',
        'order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the travel plan that owns the activity
     *
     * @return BelongsTo<TravelPlan>
     */
    public function travelPlan(): BelongsTo
    {
        return $this->belongsTo(TravelPlan::class);
    }
}
