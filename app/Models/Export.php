<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $travel_plan_id
 * @property string $type
 * @property string $format
 * @property string $status
 * @property string|null $file_path
 * @property string|null $file_url
 * @property int|null $file_size
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User $user
 * @property-read TravelPlan $travelPlan
 */
class Export extends Model
{
    /** @use HasFactory<\Database\Factories\ExportFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'travel_plan_id',
        'type',
        'format',
        'status',
        'file_path',
        'file_url',
        'file_size',
        'error_message',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'file_size' => 'integer',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the export
     *
     * @return BelongsTo<User>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the travel plan that owns the export
     *
     * @return BelongsTo<TravelPlan>
     */
    public function travelPlan(): BelongsTo
    {
        return $this->belongsTo(TravelPlan::class);
    }
}
