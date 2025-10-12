<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Session Management Service
 *
 * Manages session timeouts, warnings, and extensions.
 * Provides functionality for 120-minute session lifecycle with 5-minute warning.
 */
class SessionService
{
    /**
     * Session lifetime in minutes (from config).
     */
    private const SESSION_LIFETIME = 120; // 2 hours

    /**
     * Warning threshold in minutes (show warning 5 min before expiry).
     */
    private const WARNING_THRESHOLD = 5;

    /**
     * Get remaining session time in seconds.
     */
    public function getRemainingTime(): int
    {
        $lastActivity = session('last_activity', now()->timestamp);
        $expiresAt = $lastActivity + (self::SESSION_LIFETIME * 60);

        return max(0, $expiresAt - now()->timestamp);
    }

    /**
     * Check if session warning should be displayed.
     */
    public function shouldShowWarning(): bool
    {
        $remaining = $this->getRemainingTime();
        $warningThreshold = self::WARNING_THRESHOLD * 60;

        return $remaining > 0 && $remaining <= $warningThreshold;
    }

    /**
     * Extend current session (refresh last activity).
     */
    public function extendSession(): void
    {
        session(['last_activity' => now()->timestamp]);
        session()->save();
    }

    /**
     * Get session expiry timestamp.
     */
    public function getExpiryTime(): int
    {
        $lastActivity = session('last_activity', now()->timestamp);

        return $lastActivity + (self::SESSION_LIFETIME * 60);
    }

    /**
     * Get session lifetime in minutes.
     */
    public function getSessionLifetime(): int
    {
        return self::SESSION_LIFETIME;
    }

    /**
     * Get warning threshold in minutes.
     */
    public function getWarningThreshold(): int
    {
        return self::WARNING_THRESHOLD;
    }

    /**
     * Check if session has expired.
     */
    public function isExpired(): bool
    {
        return $this->getRemainingTime() === 0;
    }

    /**
     * Get remaining time formatted as "MM:SS".
     */
    public function getRemainingTimeFormatted(): string
    {
        $seconds = $this->getRemainingTime();
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        return sprintf('%02d:%02d', $minutes, $remainingSeconds);
    }
}
