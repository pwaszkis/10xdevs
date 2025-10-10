<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Plan Point Model
 *
 * @property int $id
 * @property int $plan_day_id
 * @property int $order_number
 * @property string $day_part
 * @property string $name
 * @property string $description
 * @property string $justification
 * @property int $duration_minutes
 * @property string $google_maps_url
 * @property float|null $location_lat
 * @property float|null $location_lng
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class PlanPoint extends Model
{
    /** @use HasFactory<\Database\Factories\PlanPointFactory> */
    use HasFactory;

    protected $fillable = [
        'plan_day_id',
        'order_number',
        'day_part',
        'name',
        'description',
        'justification',
        'duration_minutes',
        'google_maps_url',
        'location_lat',
        'location_lng',
    ];

    protected $casts = [
        'order_number' => 'integer',
        'duration_minutes' => 'integer',
        'location_lat' => 'float',
        'location_lng' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the day that owns the point.
     *
     * @return BelongsTo<PlanDay>
     */
    public function day(): BelongsTo
    {
        return $this->belongsTo(PlanDay::class, 'plan_day_id');
    }
}
