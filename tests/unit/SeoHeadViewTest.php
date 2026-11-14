<?php

use App\Libraries\SeoMetaBuilder;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Example tests for the SEO head rendered by the landing view.
 *
 * Validates: Requirements 11.1, 11.3, 11.4, 11.6, 11.8, 11.9, 11.10
 *
 * @internal
 */
final class SeoHeadViewTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper('url');
    }

    /** @param array<string, mixed> $overrides */
    private function model(array $overrides = []): array
    {
        return array_replace([
            'lang'         => 'en',
            'languages'    => ['en', 'id', 'ms'],
            'hero'         => ['title' => 'Drive Bali', 'subtitle' => 'Rent a car with ease'],
            'about'        => 'About text',
            'galleryTitle' => '',
            'contact'      => ['body' => '', 'email' => 'a@b.co', 'address' => 'Jl. Raya', 'whatsapp' => null],
            'fleet'        => [],
            'news'         => [],
            'faq'          => [],
            'gallery'      => [],
        ], $overrides);
    }

    /**
     * @param array<string, mixed> $overrides
     * @param array<string, mixed> $config
     */
    private function head(array $overrides = [], array $config = [], string $lang = 'en'): string
    {
        $model        = $this->model($overrides);
        $model['lang'] = $lang;
        $model['seo'] = SeoMetaBuilder::build($model, $config, 'http://localhost/', $lang);

        $html = view('landing/index', $model, ['saveData' => false]);
        $this->assertSame(1, preg_match('#<head[^>]*>(.*?)</head>#s', $html, $m));

        $decoded = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return preg_replace('/<!--.*?-->/s', '', $decoded);
    }

    public function testTitleDescriptionAndCanonical(): void
    {
        $head = $this->head();

        $this->assertStringContainsString('<title>Drive Bali</title>', $head);
        $this->assertStringContainsString('<meta name="description" content="Rent a car with ease">', $head);
        $this->assertStringContainsString('<link rel="canonical" href="http://localhost/">', $head);
    }

    public function testCanonicalIncludesLangForNonDefault(): void
    {
        $head = $this->head([], [], 'id');

        $this->assertStringContainsString('<link rel="canonical" href="http://localhost/?lang=id">', $head);
    }

    public function testThreeHreflangPlusXDefault(): void
    {
        $head = $this->head();

        $this->assertSame(4, preg_match_all('#<link rel="alternate" hreflang="([^"]+)"#', $head, $m));
        $this->assertSame(['en', 'id', 'ms', 'x-default'], $m[1]);
    }

    public function testOpenGraphAndTwitterTags(): void
    {
        $head = $this->head();

        foreach (['og:title', 'og:description', 'og:type', 'og:url', 'og:image', 'og:locale'] as $p) {
            $this->assertStringContainsString('<meta property="' . $p . '" content="', $head, $p);
        }
        foreach (['twitter:card', 'twitter:title', 'twitter:description', 'twitter:image'] as $n) {
            $this->assertStringContainsString('<meta name="' . $n . '" content="', $head, $n);
        }
        $this->assertStringContainsString('<meta property="og:image" content="http://localhost/assets/img/og-default.jpg">', $head);
    }

    public function testJsonLdDecodesAndOmitsFaqPageWithoutFaq(): void
    {
        $head = $this->head();

        $this->assertSame(1, preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $head, $m));
        $data = json_decode($m[1][0], true);
        $this->assertIsArray($data);
        $this->assertSame('AutoRental', $data['@type']);
        $this->assertStringNotContainsString('FAQPage', $head);
    }

    public function testJsonLdIncludesFaqPageWhenFaqItemsExist(): void
    {
        $head = $this->head(['faq' => [['question' => 'Q1?', 'answer' => 'A1']]]);

        $this->assertSame(1, preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $head, $m));
        $data = json_decode($m[1], true);
        $this->assertIsArray($data);
        $types = array_column($data, '@type');
        $this->assertSame(['AutoRental', 'FAQPage'], $types);
        $this->assertSame('Q1?', $data[1]['mainEntity'][0]['name']);
    }

    public function testKeywordsTagOnlyWhenConfigured(): void
    {
        $this->assertStringNotContainsString('name="keywords"', $this->head());

        $head = $this->head([], ['meta_keywords_en' => 'car rental, bali']);
        $this->assertStringContainsString('<meta name="keywords" content="car rental, bali">', $head);
    }
}
