<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * Pure User-Agent classifier. Returns exactly one of desktop|mobile|tablet|bot.
 */
final class DeviceDetector
{
    public const DESKTOP = 'desktop';
    public const MOBILE  = 'mobile';
    public const TABLET  = 'tablet';
    public const BOT     = 'bot';

    private const BOT_PATTERN = '/bot|crawl|spider|slurp|bingpreview|mediapartners|facebookexternalhit|facebot|embedly|quora link preview|whatsapp|telegram|pinterest|headlesschrome|phantomjs|lighthouse|pingdom|uptimerobot|curl\/|wget\/|python-requests|python-urllib|java\/|libwww|httpclient|okhttp|go-http-client|scrapy|postman|monitor|fetch/i';

    private const TABLET_PATTERN = '/ipad|tablet|kindle|silk\/|playbook|nexus (?:7|9|10)|sm-t\d|xoom/i';

    private const MOBILE_PATTERN = '/mobi|iphone|ipod|android|blackberry|windows phone|opera mini|iemobile|webos/i';

    /**
     * Classify a User-Agent string. Null, empty or whitespace-only UAs are bots.
     */
    public static function classify(?string $userAgent): string
    {
        if ($userAgent === null || trim($userAgent) === '') {
            return self::BOT;
        }

        if (self::isBot($userAgent)) {
            return self::BOT;
        }

        if (preg_match(self::TABLET_PATTERN, $userAgent) === 1) {
            return self::TABLET;
        }

        // Android without the "Mobile" token is a tablet.
        if (stripos($userAgent, 'android') !== false && stripos($userAgent, 'mobile') === false) {
            return self::TABLET;
        }

        if (preg_match(self::MOBILE_PATTERN, $userAgent) === 1) {
            return self::MOBILE;
        }

        return self::DESKTOP;
    }

    public static function isBot(?string $userAgent): bool
    {
        if ($userAgent === null || trim($userAgent) === '') {
            return true;
        }

        return preg_match(self::BOT_PATTERN, $userAgent) === 1;
    }
}
