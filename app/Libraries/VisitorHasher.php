<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * Pure visitor hasher. Produces a daily-rotating SHA-256 hash from the client
 * IP, User-Agent, visit date and an application-derived salt. The raw IP is
 * never part of the output. No database or HTTP access.
 */
final class VisitorHasher
{
    /**
     * @return string 64-character lowercase hexadecimal string
     */
    public static function hash(string $ip, string $userAgent, string $date, string $salt): string
    {
        return hash('sha256', implode('|', [$ip, $userAgent, $date, $salt]));
    }
}
