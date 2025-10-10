<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * User Preference Model
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $language
 * @property string|null $timezone
 * @property string|null $currency
 * @property bool $notifications_enabled
 * @property bool $email_notifications
 * @property bool $push_notifications
 * @property string|null $theme
 * @property list<string>|null $interests_categories
 * @property string|null $travel_pace
 * @property string|null $budget_level
 * @property string|null $transport_preference
 * @property string|null $restrictions
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class UserPreference extends Model
{
    /** @use HasFactory<\Database\Factories\UserPreferenceFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'language',
        'timezone',
        'currency',
        'notifications_enabled',
        'email_notifications',
        'push_notifications',
        'theme',
        'interests_categories',
        'travel_pace',
        'budget_level',
        'transport_preference',
        'restrictions',
    ];

    protected $casts = [
        'interests_categories' => 'array',
        'notifications_enabled' => 'boolean',
        'email_notifications' => 'boolean',
        'push_notifications' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the preferences.
     *
     * @return BelongsTo<User>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
