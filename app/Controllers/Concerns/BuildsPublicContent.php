<?php

namespace App\Controllers\Concerns;

use App\Libraries\ContentFilter;
use App\Libraries\LanguageResolver;
use App\Libraries\WhatsAppLinkBuilder;
use Throwable;

/**
 * Content building shared by the public controllers (landing page and the
 * Fleet / Gallery / News & Information listing pages). Expects the using class
 * to extend PublicBaseController ($this->lang, $this->request) and, for
 * trackVisit(), to declare a `$visitorTracker` property.
 */
trait BuildsPublicContent
{
    protected const FLEET_PLACEHOLDER = 'assets/img/fleet-placeholder.svg';

    /**
     * Call an array-returning proxy; any failure/empty yields [] (never aborts).
     *
     * @return array<mixed>
     */
    protected function section(?object $proxy, string $method, bool $assoc = false): array
    {
        if ($proxy === null) {
            return [];
        }

        try {
            $result = $proxy->{$method}();
            $status = (int) ($result['status'] ?? 500);
            $data   = $result['data'] ?? [];
            if ($status < 200 || $status > 299 || (! is_array($data) && ! is_object($data))) {
                return [];
            }
            $data = json_decode((string) json_encode($data), true);
            if (! is_array($data)) {
                return [];
            }

            return $assoc ? $data : array_values(array_filter($data, 'is_array'));
        } catch (Throwable $e) {
            log_message('error', 'Landing section {method} failed: {msg}', ['method' => $method, 'msg' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array<int, array<string, mixed>>
     */
    protected function buildFleetItems(array $rows, string $waNum): array
    {
        $images = ContentFilter::fleetImageSources($rows, self::FLEET_PLACEHOLDER);
        $fleet  = [];
        foreach (array_values($rows) as $i => $row) {
            $name    = LanguageResolver::resolve($this->translations($row, 'name'), $this->lang);
            $fleet[] = [
                'id'          => $row['id'] ?? null,
                'name'        => $name,
                'description' => LanguageResolver::resolve($this->translations($row, 'description'), $this->lang),
                'image'       => $images[$i] ?? self::FLEET_PLACEHOLDER,
                'whatsapp'    => WhatsAppLinkBuilder::build($waNum, mb_substr($this->bookingText($name), 0, 200)),
            ];
        }

        return $fleet;
    }

    /**
     * Published news only (publish date reached), newest first.
     *
     * @param array<int, array<string, mixed>> $rows
     *
     * `url` points to the detail page (empty when the row has no id); `excerpt` is a short
     * single-line preview of the body for the cards.
     *
     * @return array<int, array{id: int, title: string, body: string, excerpt: string, publish_date: string, image: string, path: string, url: string}>
     */
    protected function buildNewsItems(array $rows): array
    {
        helper('ui');
        $out = [];
        foreach (ContentFilter::publishedNewsDescending($rows, date('Y-m-d H:i:s')) as $row) {
            $id    = (int) ($row['id'] ?? 0);
            $title = LanguageResolver::resolve($this->translations($row, 'title'), $this->lang);
            $body  = LanguageResolver::resolve($this->translations($row, 'body'), $this->lang);
            // Slug from the English title so the address stays the same in every language.
            $slugSource = trim((string) ($row['title_en'] ?? '')) !== '' ? (string) $row['title_en'] : $title;
            $path       = $id > 0 ? static::newsPath($id, $slugSource) : '';

            $out[] = [
                'id'           => $id,
                'title'        => $title,
                'body'         => $body,
                'excerpt'      => static::newsExcerpt($body),
                'publish_date' => $row['publish_date'] ?? '',
                'date_label'   => ui_date((string) ($row['publish_date'] ?? ''), $this->lang),
                'image'        => trim((string) ($row['image_path'] ?? '')),
                'path'         => $path,
                'url'          => $path !== '' ? site_url($path) : '',
            ];
        }

        return $out;
    }

    /** Relative path of a news detail page: news/{id}/{slug} (slug omitted when empty). */
    protected static function newsPath(int $id, string $title): string
    {
        helper('url');
        $slug = mb_substr(url_title(mb_strtolower(trim($title)), '-', true), 0, 80);
        $slug = trim($slug, '-');

        return 'news/' . $id . ($slug !== '' ? '/' . $slug : '');
    }

    /** Body collapsed to one line and cut at a word boundary, with an ellipsis when shortened. */
    protected static function newsExcerpt(string $body, int $max = 180): string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', $body));
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        $cut   = mb_substr($text, 0, $max);
        $space = mb_strrpos($cut, ' ');
        if ($space !== false && $space > $max * 0.6) {
            $cut = mb_substr($cut, 0, $space);
        }

        return rtrim($cut, " ,.;:-") . '...';
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array<int, array{image: string, caption: string}>
     */
    protected function buildGalleryItems(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'image'   => (string) ($row['image_path'] ?? ''),
                'caption' => LanguageResolver::resolve($this->translations($row, 'caption'), $this->lang),
            ];
        }

        return $out;
    }

    /** @return array<string, mixed> */
    protected function translations(array $row, string $field): array
    {
        return [
            'en' => $row[$field . '_en'] ?? null,
            'id' => $row[$field . '_id'] ?? null,
            'ms' => $row[$field . '_ms'] ?? null,
        ];
    }

    protected function configText(array $config, string $field, string $lang): string
    {
        return LanguageResolver::resolve($this->translations($config, $field), $lang);
    }

    protected function bookingText(string $fleetName): string
    {
        $templates = [
            'en' => 'Hello, I would like to book: ',
            'id' => 'Halo, saya ingin memesan: ',
            'ms' => 'Hello, saya ingin menempah: ',
        ];

        return ($templates[$this->lang] ?? $templates['en']) . $fleetName;
    }

    /** Log the page view; VisitorTracker swallows its own failures. */
    protected function trackVisit(): void
    {
        try {
            $uri = $this->request->getUri();
            $this->visitorTracker->record(
                '/' . ltrim($uri->getPath(), '/'),
                (string) $this->request->getIPAddress(),
                $this->request->getUserAgent()?->getAgentString(),
                $this->lang,
                $this->request->getServer('HTTP_REFERER'),
                $uri->getHost()
            );
        } catch (Throwable $e) {
            log_message('error', 'Landing visit tracking failed: {msg}', ['msg' => $e->getMessage()]);
        }
    }
}