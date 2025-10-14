<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * User Event Model
 *
 * Tracks user behavior and actions for analytics.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $event_type
 * @property array<string, mixed>|null $event_data
 * @property \Carbon\Carbon $created_at
 * @property-read \App\Models\User|null $user
 */
class UserEvent extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_events';

    /**
     * Indicates if the model should be timestamped.
     * Events are immutable, so we only need created_at.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'event_type',
        'event_data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'event_data' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Available event types
     */
    public const EVENT_LOGIN = 'login';

    public const EVENT_LOGOUT = 'logout';

    public const EVENT_ONBOARDING_STARTED = 'onboarding_started';

    public const EVENT_ONBOARDING_STEP_COMPLETED = 'onboarding_step_completed';

    public const EVENT_ONBOARDING_COMPLETED = 'onboarding_completed';

    public const EVENT_PLAN_CREATED = 'plan_created';

    public const EVENT_PLAN_SAVED_AS_DRAFT = 'plan_saved_as_draft';

    public const EVENT_AI_GENERATED = 'ai_generated';

    public const EVENT_AI_REGENERATED = 'ai_regenerated';

    public const EVENT_PDF_EXPORTED = 'pdf_exported';

    public const EVENT_FEEDBACK_SUBMITTED = 'feedback_submitted';

    /**
     * Get the user that owns the event.
     *
     * @return BelongsTo<User, UserEvent>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create a new event.
     *
     * @param  array<string, mixed>|null  $eventData
     */
    public static function log(string $eventType, ?int $userId = null, ?array $eventData = null): self
    {
        return self::create([
            'user_id' => $userId,
            'event_type' => $eventType,
            'event_data' => $eventData,
        ]);
    }
}
