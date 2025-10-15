<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string|null $password
 * @property string|null $google_id
 * @property string|null $avatar_url
 * @property bool $onboarding_completed
 * @property string|null $remember_token
 * @property string|null $nickname
 * @property string|null $home_location
 * @property \Illuminate\Support\Carbon|null $onboarding_completed_at
 * @property int $onboarding_step
 * @property int $ai_generations_count_current_month
 * @property \Illuminate\Support\Carbon|null $ai_generations_reset_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 *
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    use Notifiable;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar_url',
        'onboarding_completed',
        'nickname',
        'home_location',
        'onboarding_completed_at',
        'onboarding_step',
        'ai_generations_count_current_month',
        'ai_generations_reset_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'onboarding_completed' => 'boolean',
            'onboarding_completed_at' => 'datetime',
            'onboarding_step' => 'integer',
            'ai_generations_count_current_month' => 'integer',
            'ai_generations_reset_at' => 'datetime',
        ];
    }

    /**
     * Get the user's preferences.
     *
     * @return HasOne<UserPreference>
     */
    public function preferences(): HasOne
    {
        return $this->hasOne(UserPreference::class);
    }

    /**
     * Get the user's travel plans.
     *
     * @return HasMany<TravelPlan>
     */
    public function travelPlans(): HasMany
    {
        return $this->hasMany(TravelPlan::class);
    }

    /**
     * Check if user has completed onboarding.
     */
    public function hasCompletedOnboarding(): bool
    {
        return $this->onboarding_completed && $this->onboarding_completed_at !== null;
    }

    /**
     * Get remaining AI generations for current month.
     */
    public function getRemainingAiGenerations(int $limit = 10): int
    {
        return max(0, $limit - $this->ai_generations_count_current_month);
    }

    /**
     * Get the display name (prefers nickname, falls back to name).
     */
    public function getDisplayNameAttribute(): ?string
    {
        return $this->nickname ?? $this->name;
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        // TODO: Implement actual admin check logic
        return false;
    }

    /**
     * Check if user registered via OAuth.
     */
    public function isOAuthUser(): bool
    {
        return $this->google_id !== null;
    }

    /**
     * Check if user has verified email.
     */
    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    /**
     * Check if user needs onboarding.
     */
    public function needsOnboarding(): bool
    {
        return ! $this->onboarding_completed;
    }

    /**
     * Mark onboarding as completed.
     */
    public function markOnboardingCompleted(): void
    {
        $this->update(['onboarding_completed' => true]);
    }

    /**
     * Check if user has completed their profile.
     * Profile is complete when all required fields are filled.
     */
    public function hasCompletedProfile(): bool
    {
        if (! $this->hasCompletedOnboarding()) {
            return false;
        }

        // Check if all required user fields are filled
        if (empty($this->nickname) || empty($this->home_location)) {
            return false;
        }

        // Check if preferences exist and all required fields are filled
        if (! $this->preferences) {
            return false;
        }

        $preferences = $this->preferences;

        return ! empty($preferences->interests_categories)
            && ! empty($preferences->travel_pace)
            && ! empty($preferences->budget_level)
            && ! empty($preferences->transport_preference)
            && ! empty($preferences->restrictions);
    }

    /**
     * Get the user's AI generations.
     *
     * @return HasMany<AIGeneration>
     */
    public function aiGenerations(): HasMany
    {
        return $this->hasMany(AIGeneration::class);
    }

    /**
     * Get the user's events.
     *
     * @return HasMany<UserEvent>
     */
    public function userEvents(): HasMany
    {
        return $this->hasMany(UserEvent::class);
    }
}
