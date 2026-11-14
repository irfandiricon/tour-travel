<?php

namespace App\Libraries;

/**
 * Pure validation for admin (CMS login) accounts. Returns field => message; empty means valid.
 * Uniqueness and "who is signed in" rules need the database and live in the api/v1 controller.
 */
class AdminUserValidator
{
    public const USERNAME_MIN = 3;
    public const USERNAME_MAX = 50;
    public const PASSWORD_MIN = 8;
    /** bcrypt only uses the first 72 bytes, so longer passwords would be silently truncated. */
    public const PASSWORD_MAX_BYTES = 72;
    public const STATUSES = ['active', 'inactive'];

    /**
     * @param array<string,mixed> $input    username, password, password_confirm, status
     * @param bool                $creating password is required when creating, optional when editing
     *
     * @return array<string,string>
     */
    public static function validate(array $input, bool $creating): array
    {
        $errors = [];

        $username = is_string($input['username'] ?? null) ? trim($input['username']) : '';
        $length   = mb_strlen($username);
        if ($username === '') {
            $errors['username'] = 'Username is required.';
        } elseif ($length < self::USERNAME_MIN || $length > self::USERNAME_MAX) {
            $errors['username'] = 'Username must be ' . self::USERNAME_MIN . ' to ' . self::USERNAME_MAX . ' characters.';
        } elseif (preg_match('/^[A-Za-z0-9._-]+$/', $username) !== 1) {
            $errors['username'] = 'Username may only contain letters, numbers, dot (.), dash (-) and underscore (_).';
        }

        $password = is_string($input['password'] ?? null) ? $input['password'] : '';
        $confirm  = is_string($input['password_confirm'] ?? null) ? $input['password_confirm'] : '';
        if ($password === '') {
            if ($creating) {
                $errors['password'] = 'Password is required.';
            }
        } elseif (mb_strlen($password) < self::PASSWORD_MIN) {
            $errors['password'] = 'Password must be at least ' . self::PASSWORD_MIN . ' characters.';
        } elseif (strlen($password) > self::PASSWORD_MAX_BYTES) {
            $errors['password'] = 'Password must be at most ' . self::PASSWORD_MAX_BYTES . ' characters.';
        } elseif (trim($password) === '') {
            $errors['password'] = 'Password cannot be only spaces.';
        }

        if ($password !== '' && ! isset($errors['password']) && ! hash_equals($password, $confirm)) {
            $errors['password_confirm'] = 'Passwords do not match.';
        }

        $status = $input['status'] ?? null;
        if ($status !== null && $status !== '' && ! in_array($status, self::STATUSES, true)) {
            $errors['status'] = 'Status must be active or inactive.';
        }

        return $errors;
    }

    /**
     * Rules that keep at least one way into the CMS. Returns an error message, or null when allowed.
     *
     * @param bool $isSelf       the account being changed is the signed-in admin
     * @param bool $wasActive    the account is active now
     * @param bool $staysActive  the account is still active after the change (false when deleting)
     * @param int  $activeCount  number of active accounts right now
     */
    public static function protectionError(bool $deleting, bool $isSelf, bool $wasActive, bool $staysActive, int $activeCount): ?string
    {
        if ($isSelf && $deleting) {
            return 'You cannot delete the account you are signed in with.';
        }
        if ($isSelf && ! $staysActive) {
            return 'You cannot deactivate the account you are signed in with.';
        }
        if ($wasActive && ! $staysActive && $activeCount <= 1) {
            return 'At least one active user is required.';
        }

        return null;
    }
}