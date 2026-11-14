<?php

namespace App\Libraries;

/**
 * Pure login-lockout logic (Req 3.5). No DB / clock access: callers pass `$now`.
 *
 * Policy: the 5th failed attempt within a 15-minute window locks the account
 * for 15 minutes. The window is sliding: a failure counts toward the streak
 * only if the previous failure happened less than 15 minutes earlier.
 */
class LoginThrottle
{
    public const MAX_ATTEMPTS     = 5;
    public const WINDOW_SECONDS   = 900; // 15 minutes
    public const LOCK_SECONDS     = 900; // 15 minutes

    /** True while `locked_until` is in the future. */
    public static function isLocked(?string $lockedUntil, int $now): bool
    {
        if ($lockedUntil === null || $lockedUntil === '') {
            return false;
        }
        $ts = strtotime($lockedUntil);

        return $ts !== false && $ts > $now;
    }

    /**
     * Compute the new state after a failed attempt.
     *
     * @param int      $failedAttempts  stored counter before this failure
     * @param int|null $lastFailureTs   unix time of the previous failure (null if unknown/none)
     * @param int      $now             unix time of this failure
     *
     * @return array{failed_attempts:int, locked_until:?string}
     */
    public static function registerFailure(int $failedAttempts, ?int $lastFailureTs, int $now): array
    {
        $withinWindow = $lastFailureTs !== null && ($now - $lastFailureTs) < self::WINDOW_SECONDS;
        $attempts     = ($withinWindow ? max(0, $failedAttempts) : 0) + 1;

        $lockedUntil = null;
        if ($attempts >= self::MAX_ATTEMPTS) {
            $lockedUntil = date('Y-m-d H:i:s', $now + self::LOCK_SECONDS);
        }

        return ['failed_attempts' => $attempts, 'locked_until' => $lockedUntil];
    }

    /** @return array{failed_attempts:int, locked_until:null} state after a successful login. */
    public static function registerSuccess(): array
    {
        return ['failed_attempts' => 0, 'locked_until' => null];
    }
}
