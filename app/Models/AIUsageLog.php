<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIUsageLog extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'model',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'estimated_cost',
        'request_type',
        'error_message',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estimated_cost' => 'decimal:6',
        ];
    }

    /**
     * @return BelongsTo<User, AIUsageLog>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
