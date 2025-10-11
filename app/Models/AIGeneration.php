<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AI Generation Model
 *
 * Tracks all AI plan generation attempts with cost metrics and status.
 * Each regeneration creates a new record (no updates of existing).
 *
 * @property int $id
 * @property int $travel_plan_id
 * @property int $user_id
 * @property string $status
 * @property string|null $model_used
 * @property int|null $tokens_used
 * @property float|null $cost_usd
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class AIGeneration extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'ai_generations';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'travel_plan_id',
        'user_id',
        'status',
        'model_used',
        'tokens_used',
        'cost_usd',
        'error_message',
        'started_at',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tokens_used' => 'integer',
            'cost_usd' => 'decimal:4',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
    ];

    /**
     * Get the user that requested the generation.
     *
     * @return BelongsTo<User, AIGeneration>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the travel plan being generated.
     *
     * @return BelongsTo<TravelPlan, AIGeneration>
     */
    public function travelPlan(): BelongsTo
    {
        return $this->belongsTo(TravelPlan::class);
    }

    /**
     * Scope a query to only include generations for a specific user.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<AIGeneration>  $query
     * @return \Illuminate\Database\Eloquent\Builder<AIGeneration>
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include generations from the current month.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<AIGeneration>  $query
     * @return \Illuminate\Database\Eloquent\Builder<AIGeneration>
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }

    /**
     * Scope a query to only include completed generations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<AIGeneration>  $query
     * @return \Illuminate\Database\Eloquent\Builder<AIGeneration>
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include failed generations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<AIGeneration>  $query
     * @return \Illuminate\Database\Eloquent\Builder<AIGeneration>
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope a query to only include pending generations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<AIGeneration>  $query
     * @return \Illuminate\Database\Eloquent\Builder<AIGeneration>
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include processing generations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<AIGeneration>  $query
     * @return \Illuminate\Database\Eloquent\Builder<AIGeneration>
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Mark generation as processing.
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    /**
     * Mark generation as completed.
     */
    public function markAsCompleted(int $tokensUsed, float $costUsd): void
    {
        $this->update([
            'status' => 'completed',
            'tokens_used' => $tokensUsed,
            'cost_usd' => $costUsd,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark generation as failed.
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);
    }

    /**
     * Get the duration of the generation in seconds.
     */
    public function getDurationInSeconds(): ?int
    {
        if ($this->started_at === null || $this->completed_at === null) {
            return null;
        }

        return (int) $this->started_at->diffInSeconds($this->completed_at);
    }

    /**
     * Check if generation is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if generation is processing.
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Check if generation is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if generation failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
