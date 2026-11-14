<?php

namespace App\Libraries;

/**
 * Pure SEO head-metadata builder. No database, HTTP, or framework service access.
 */
final class SeoMetaBuilder
{
    public const SUPPORTED       = ['en', 'id', 'ms'];
    public const DEFAULT_LANG    = 'en';
    public const DESCRIPTION_MAX = 160;
    public const DEFAULT_TITLE   = 'Travel Rental';
    public const DEFAULT_IMAGE   = 'assets/img/og-default.jpg';

    private const LOCALES = ['en' => 'en_US', 'id' => 'id_ID', 'ms' => 'ms_MY'];

    /**
     * @param array<string,mixed> $model  landing view-model
     * @param array<string,mixed> $config site_config row including SEO fields
     * @return array<string,mixed>
     */
    public static function build(array $model, array $config, string $baseUrl, string $lang): array
    {
        $lang = LanguageResolver::normalize($lang);

        $siteName  = self::localized($config, 'site_name', $lang);
        $heroTitle = self::str($model['hero']['title'] ?? '');
        $heroSub   = self::str($model['hero']['subtitle'] ?? '');

        if ($heroTitle !== '' && $siteName !== '' && $heroTitle !== $siteName) {
            $title = $heroTitle . ' | ' . $siteName;
        } elseif ($heroTitle !== '') {
            $title = $heroTitle;
        } elseif ($siteName !== '') {
            $title = $siteName;
        } else {
            $title = self::DEFAULT_TITLE;
        }

        $description = '';
        foreach ([self::localized($config, 'meta_description', $lang), $heroSub, self::str($model['about'] ?? '')] as $candidate) {
            if (trim($candidate) !== '') {
                $description = $candidate;
                break;
            }
        }
        $description = self::truncate(trim($description));

        $keywords = self::localized($config, 'meta_keywords', $lang);
        $keywords = $keywords !== '' ? $keywords : null;

        $canonical = self::urlFor($baseUrl, $lang);
        $image     = self::ogImage($model, $config, $baseUrl);
        $ogTitle   = $siteName !== '' && $title === self::DEFAULT_TITLE ? $siteName : $title;

        return [
            'title'       => $title,
            'description' => $description,
            'keywords'    => $keywords,
            'robots'      => 'index, follow',
            'canonical'   => $canonical,
            'alternates'  => self::alternates($baseUrl),
            'og'          => [
                'og:title'       => $ogTitle,
                'og:description' => $description,
                'og:type'        => 'website',
                'og:url'         => $canonical,
                'og:image'       => $image,
                'og:locale'      => self::LOCALES[$lang],
            ],
            'twitter'     => [
                'twitter:card'        => 'summary_large_image',
                'twitter:title'       => $ogTitle,
                'twitter:description' => $description,
                'twitter:image'       => $image,
            ],
            'jsonld'      => self::jsonLd($model, $config, $baseUrl, $lang),
        ];
    }

    /**
     * Head metadata for a sub page (Fleet, Gallery, News & Information).
     *
     * @param array<string,mixed> $model  needs `brand` (string) and optionally `gallery` (items with `image`) for og:image
     * @param array<string,mixed> $config site_config row including SEO fields
     * @return array<string,mixed>
     */
    public static function buildPage(
        array $model,
        array $config,
        string $baseUrl,
        string $lang,
        string $path,
        string $pageTitle,
        string $pageDescription,
        string $homeLabel = 'Home',
        int $page = 1
    ): array {
        $lang     = LanguageResolver::normalize($lang);
        $siteName = self::localized($config, 'site_name', $lang);
        if ($siteName === '') {
            $siteName = self::str($model['brand'] ?? '');
        }

        $baseTitle = $page > 1 ? $pageTitle . ' - ' . $page : $pageTitle;
        $title     = $siteName !== '' && $siteName !== $pageTitle ? $baseTitle . ' | ' . $siteName : $baseTitle;

        $description = self::truncate(trim($pageDescription !== '' ? $pageDescription : self::localized($config, 'meta_description', $lang)));
        $keywords    = self::localized($config, 'meta_keywords', $lang);

        $canonical = self::urlFor($baseUrl, $lang, $path, $page);
        $image     = self::ogImage($model, $config, $baseUrl);

        return [
            'title'       => $title,
            'description' => $description,
            'keywords'    => $keywords !== '' ? $keywords : null,
            'robots'      => 'index, follow',
            'canonical'   => $canonical,
            'alternates'  => self::alternates($baseUrl, $path, $page),
            'og'          => [
                'og:title'       => $title,
                'og:description' => $description,
                'og:type'        => 'website',
                'og:url'         => $canonical,
                'og:image'       => $image,
                'og:locale'      => self::LOCALES[$lang],
            ],
            'twitter'     => [
                'twitter:card'        => 'summary_large_image',
                'twitter:title'       => $title,
                'twitter:description' => $description,
                'twitter:image'       => $image,
            ],
            'jsonld'      => [[
                '@context'        => 'https://schema.org',
                '@type'           => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => $homeLabel, 'item' => self::urlFor($baseUrl, $lang)],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => $pageTitle, 'item' => self::urlFor($baseUrl, $lang, $path)],
                ],
            ]],
        ];
    }

    /** Multibyte-safe truncation to at most $max characters. */
    public static function truncate(string $text, int $max = self::DESCRIPTION_MAX): string
    {
        if ($max <= 0) {
            return '';
        }

        return mb_strlen($text, 'UTF-8') <= $max ? $text : mb_substr($text, 0, $max, 'UTF-8');
    }

    /** @return list<array{hreflang:string,href:string}> */
    public static function alternates(string $baseUrl, string $path = '', int $page = 1): array
    {
        $out = [];
        foreach (self::SUPPORTED as $l) {
            $out[] = ['hreflang' => $l, 'href' => self::urlFor($baseUrl, $l, $path, $page)];
        }
        $out[] = ['hreflang' => 'x-default', 'href' => self::urlFor($baseUrl, self::DEFAULT_LANG, $path, $page)];

        return $out;
    }

    /**
     * @param array<string,mixed> $model
     * @param array<string,mixed> $config
     * @return list<array<string,mixed>>
     */
    public static function jsonLd(array $model, array $config, string $baseUrl, string $lang): array
    {
        $lang     = LanguageResolver::normalize($lang);
        $siteName = self::localized($config, 'site_name', $lang);
        if ($siteName === '') {
            $siteName = self::str($model['hero']['title'] ?? '');
        }
        if ($siteName === '') {
            $siteName = self::DEFAULT_TITLE;
        }

        $description = '';
        foreach ([self::localized($config, 'meta_description', $lang), self::str($model['hero']['subtitle'] ?? ''), self::str($model['about'] ?? '')] as $candidate) {
            if (trim($candidate) !== '') {
                $description = $candidate;
                break;
            }
        }

        $business = [
            '@context'    => 'https://schema.org',
            '@type'       => 'AutoRental',
            'name'        => $siteName,
            'url'         => self::urlFor($baseUrl, $lang),
            'description' => self::truncate(trim($description)),
            'image'       => self::ogImage($model, $config, $baseUrl),
        ];

        $contact = is_array($model['contact'] ?? null) ? $model['contact'] : [];
        $email   = self::str($contact['email'] ?? ($config['contact_email'] ?? ''));
        $address = self::str($contact['address'] ?? ($config['contact_address'] ?? ''));
        $phone   = self::str($contact['phone'] ?? ($config['contact_phone'] ?? ($config['whatsapp_number'] ?? '')));
        if ($email !== '') {
            $business['email'] = $email;
        }
        if ($address !== '') {
            $business['address'] = $address;
        }
        if ($phone !== '') {
            $business['telephone'] = $phone;
        }

        $blocks = [$business];

        $entities = [];
        foreach ((array) ($model['faq'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $entities[] = [
                '@type'          => 'Question',
                'name'           => self::str($item['question'] ?? ''),
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => self::str($item['answer'] ?? '')],
            ];
        }
        if ($entities !== []) {
            $blocks[] = [
                '@context'   => 'https://schema.org',
                '@type'      => 'FAQPage',
                'mainEntity' => $entities,
            ];
        }

        return $blocks;
    }

    /** @param list<array<string,mixed>> $blocks */
    public static function encodeJsonLd(array $blocks): string
    {
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_INVALID_UTF8_SUBSTITUTE;
        $data  = count($blocks) === 1 ? $blocks[0] : $blocks;
        $json  = json_encode($data, $flags);

        return $json === false ? '{}' : $json;
    }

    /** Absolute URL of the landing page (empty $path) or a sub page, with ?page= / ?lang= when needed. */
    public static function urlFor(string $baseUrl, string $lang, string $path = '', int $page = 1): string
    {
        $url   = rtrim($baseUrl, '/') . '/' . trim($path, '/');
        $query = [];
        if ($page > 1) {
            $query['page'] = $page;
        }
        if ($lang !== self::DEFAULT_LANG) {
            $query['lang'] = $lang;
        }

        return $query === [] ? $url : $url . '?' . http_build_query($query);
    }

    private static function absolute(string $path, string $baseUrl): string
    {
        $path = trim($path);
        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }

    /**
     * @param array<string,mixed> $model
     * @param array<string,mixed> $config
     */
    private static function ogImage(array $model, array $config, string $baseUrl): string
    {
        $candidates = [];
        foreach ((array) ($model['gallery'] ?? []) as $item) {
            if (is_array($item)) {
                $candidates[] = self::str($item['image'] ?? '');
            }
        }
        if (is_array($model['hero'] ?? null)) {
            $candidates[] = self::str($model['hero']['image'] ?? '');
        }
        $candidates[] = self::str($config['og_image'] ?? '');

        foreach ($candidates as $c) {
            if (trim($c) !== '') {
                return self::absolute($c, $baseUrl);
            }
        }

        return self::absolute(self::DEFAULT_IMAGE, $baseUrl);
    }

    /** @param array<string,mixed> $row */
    private static function localized(array $row, string $field, string $lang): string
    {
        $values = [];
        foreach (self::SUPPORTED as $l) {
            $values[$l] = $row[$field . '_' . $l] ?? null;
        }

        return trim(LanguageResolver::resolve($values, $lang));
    }

    private static function str(mixed $v): string
    {
        return is_scalar($v) ? (string) $v : '';
    }
}
