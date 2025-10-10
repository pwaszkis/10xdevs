<?php

declare(strict_types=1);

namespace App\Actions\Export;

use App\Models\TravelPlan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * Generate Share Link Action
 *
 * Generates a shareable link for a travel plan.
 */
class GenerateShareLinkAction
{
    /**
     * Execute the action.
     *
     * @return array{token: string, url: string, expires_at: string}
     */
    public function execute(TravelPlan $travelPlan, int $expiresInDays = 30): array
    {
        $token = Str::random(64);
        $expiresAt = now()->addDays($expiresInDays);

        // Store the share token in the travel plan metadata
        $metadata = $travelPlan->metadata ?? [];
        $metadata['share_token'] = Crypt::encryptString($token);
        $metadata['share_expires_at'] = $expiresAt->toIso8601String();

        $travelPlan->update(['metadata' => $metadata]);

        $url = route('travel-plans.shared', ['token' => $token]);

        return [
            'token' => $token,
            'url' => $url,
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }
}
