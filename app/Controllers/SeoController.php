<?php

namespace App\Controllers;

use App\Libraries\SeoMetaBuilder;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Public crawler endpoints: /robots.txt and /sitemap.xml.
 */
class SeoController extends PublicBaseController
{
    private const DISALLOWED = ['/admin', '/signin', '/api', '/dashboard'];

    public function robots(): ResponseInterface
    {
        $lines = ['User-agent: *', 'Allow: /'];
        foreach (self::DISALLOWED as $path) {
            $lines[] = 'Disallow: ' . $path;
        }
        $lines[] = '';
        $lines[] = 'Sitemap: ' . $this->baseUrl() . '/sitemap.xml';

        return $this->response
            ->setContentType('text/plain', 'UTF-8')
            ->setBody(implode("\n", $lines) . "\n");
    }

    /** Paths listed in the sitemap: the landing page ('') and the listing pages. */
    private const SITEMAP_PATHS = ['', 'fleet', 'gallery', 'news'];

    public function sitemap(): ResponseInterface
    {
        $base = $this->baseUrl();

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

        foreach (self::SITEMAP_PATHS as $path) {
            $xml .= "  <url>\n    <loc>" . $this->xml(SeoMetaBuilder::urlFor($base, 'en', $path)) . "</loc>\n";
            foreach (SeoMetaBuilder::alternates($base, $path) as $alt) {
                $xml .= '    <xhtml:link rel="alternate" hreflang="' . $this->xml($alt['hreflang']) . '" href="' . $this->xml($alt['href']) . '"/>' . "\n";
            }
            $xml .= "  </url>\n";
        }

        $xml .= "</urlset>\n";

        return $this->response
            ->setContentType('application/xml', 'UTF-8')
            ->setBody($xml);
    }
    private function baseUrl(): string
    {
        return rtrim(base_url(), '/');
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
