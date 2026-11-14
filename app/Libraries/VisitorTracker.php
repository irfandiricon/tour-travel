<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\VisitorLogModel;
use Throwable;

/**
 * Records one anonymous page view per request into visitor_logs. Never throws:
 * any failure is logged (without the IP) and swallowed so the page still renders.
 */
final class VisitorTracker
{
    /** Path prefixes (first segment) that are never tracked. */
    private const EXCLUDED_PREFIXES = ['admin', 'api', 'signin', 'dashboard', 'track', 'assets', 'uploads'];

    private const EXCLUDED_EXACT = ['robots.txt', 'sitemap.xml', 'favicon.ico'];

    private const ASSET_EXTENSION = '/\.(?:css|js|map|png|jpe?g|gif|svg|webp|ico|woff2?|ttf|eot|txt|xml|json)$/i';

    private ?object $model;

    /**
     * @param object|null $model object exposing insert(array); defaults to VisitorLogModel
     */
    public function __construct(?object $model = null)
    {
        $this->model = $model;
    }

    /** Whether a request path should be logged. */
    public static function shouldTrack(string $path): bool
    {
        $path = trim((string) parse_url($path, PHP_URL_PATH), '/');
        if ($path === '') {
            return true;
        }

        $lower = strtolower($path);
        if (in_array($lower, self::EXCLUDED_EXACT, true)) {
            return false;
        }

        $first = explode('/', $lower, 2)[0];
        if (in_array($first, self::EXCLUDED_PREFIXES, true)) {
            return false;
        }

        return preg_match(self::ASSET_EXTENSION, $lower) !== 1;
    }

    /**
     * Referrer host, or null when absent, unparsable or equal to the site host.
     */
    public static function referrerHost(?string $referrer, string $currentHost): ?string
    {
        if ($referrer === null || trim($referrer) === '') {
            return null;
        }

        $host = parse_url(trim($referrer), PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return null;
        }

        $host = strtolower($host);
        if ($host === strtolower($currentHost)) {
            return null;
        }

        return mb_substr($host, 0, 255);
    }

    /**
     * Store a visit. Returns true when a row was inserted, false when skipped or failed.
     */
    public function record(
        string $path,
        string $ip,
        ?string $userAgent,
        string $lang,
        ?string $referrer,
        string $currentHost,
        ?string $now = null
    ): bool {
        try {
            if (! self::shouldTrack($path)) {
                return false;
            }

            $timestamp = $now ?? date('Y-m-d H:i:s');
            $date      = substr($timestamp, 0, 10);
            $ua        = $userAgent ?? '';
            $device    = DeviceDetector::classify($userAgent);

            $page = '/' . trim((string) parse_url($path, PHP_URL_PATH), '/');

            $model = $this->model ?? new VisitorLogModel();
            $model->insert([
                'visit_date'    => $date,
                'visitor_hash'  => VisitorHasher::hash($ip, $ua, $date, self::salt()),
                'page'          => mb_substr($page, 0, 255),
                'lang'          => substr($lang, 0, 2),
                'device_type'   => $device,
                'referrer_host' => self::referrerHost($referrer, $currentHost),
                'is_bot'        => DeviceDetector::isBot($userAgent) ? 1 : 0,
                'created_at'    => $timestamp,
            ]);

            return true;
        } catch (Throwable $e) {
            // Never include the IP in the log message.
            log_message('error', 'Visitor tracking failed: {msg}', ['msg' => $e->getMessage()]);

            return false;
        }
    }

    /** Salt derived from the application encryption key. */
    private static function salt(): string
    {
        $key = (string) (config('Encryption')->key ?? '');

        return hash('sha256', 'visitor-salt|' . $key);
    }
}
