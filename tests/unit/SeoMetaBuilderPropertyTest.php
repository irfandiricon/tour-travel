<?php

use App\Libraries\SeoMetaBuilder;
use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class SeoMetaBuilderPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Text generator: empty, whitespace-only, short, or long (>160 chars) multibyte strings.
     */
    private function texts(): mixed
    {
        $chunks = ['abc ', 'Sewa Mobil ', 'é日本語', '🚗 ', 'ñandú', '"q" <b>&', 'مرحبا'];

        return Generators::map(
            static function (array $t) use ($chunks): string {
                [$len, $chunk] = $t;

                return $len === 0 ? '' : mb_substr(str_repeat($chunks[$chunk], 100), 0, $len, 'UTF-8');
            },
            Generators::tuple(
                Generators::oneOf(
                    Generators::constant(0),
                    Generators::choose(1, 20),
                    Generators::choose(150, 170),
                    Generators::choose(171, 400)
                ),
                Generators::choose(0, count($chunks) - 1)
            )
        );
    }

    /**
     * Landing view-model with possibly empty/missing hero and about fields.
     */
    private function models(): mixed
    {
        return Generators::map(
            static function (array $t): array {
                [$title, $sub, $about, $withHero, $withAbout] = $t;
                $model = [];
                if ($withHero) {
                    $model['hero'] = ['title' => $title, 'subtitle' => $sub];
                }
                if ($withAbout) {
                    $model['about'] = $about;
                }

                return $model;
            },
            Generators::tuple(
                $this->texts(),
                $this->texts(),
                $this->texts(),
                Generators::bool(),
                Generators::bool()
            )
        );
    }

    /**
     * Site config with possibly missing/empty SEO fields in en/id/ms.
     */
    private function configs(): mixed
    {
        return Generators::map(
            static function (array $t): array {
                $config = [];
                $keys   = ['site_name', 'meta_description', 'meta_keywords'];
                $langs  = ['en', 'id', 'ms'];
                $i      = 0;
                foreach ($keys as $k) {
                    foreach ($langs as $l) {
                        [$present, $value] = $t[$i++];
                        if ($present) {
                            $config[$k . '_' . $l] = $value;
                        }
                    }
                }

                return $config;
            },
            Generators::tuple(...array_fill(0, 9, Generators::tuple(Generators::bool(), $this->texts())))
        );
    }

    private function baseUrls(): mixed
    {
        return Generators::elements(
            'http://localhost:8080',
            'http://localhost:8080/',
            'https://example.com',
            'https://tour.example.co.id/',
            'https://sub.example.org/app'
        );
    }

    /**
     * Strings with quotes, backslashes, angle brackets, script closers and unicode.
     */
    private function hostileTexts(): mixed
    {
        $parts = ['a', ' ', '"', "'", '\\', '<', '>', '</script>', '</SCRIPT ', '&', '/', 'é', '日本語', '🚗', "\n", '-->'];

        return Generators::map(
            static function (array $idx) use ($parts): string {
                return implode('', array_map(static fn (int $i): string => $parts[$i], $idx));
            },
            Generators::seq(Generators::choose(0, count($parts) - 1))
        );
    }

    /**
     * FAQ list of 0..N items with hostile question/answer strings.
     */
    private function faqLists(): mixed
    {
        return Generators::seq(
            Generators::map(
                static fn (array $t): array => ['question' => $t[0], 'answer' => $t[1]],
                Generators::tuple($this->hostileTexts(), $this->hostileTexts())
            )
        );
    }

    /**
     * Validates: Requirements 11.8, 11.9, 11.10
     */
    public function testJsonLdIsValidSafeAndFaqComplete(): void
    {
        // Feature: travel-rental-website, Property 24: JSON-LD validity and FAQ completeness
        $this->limitTo(100)->forAll(
            $this->faqLists(),
            $this->hostileTexts(),
            $this->hostileTexts(),
            $this->baseUrls(),
            Generators::elements('en', 'id', 'ms')
        )->then(static function (array $faq, string $siteName, string $about, string $baseUrl, string $lang): void {
            $model  = ['faq' => $faq, 'about' => $about, 'contact' => ['email' => $siteName, 'address' => $about]];
            $config = ['site_name_en' => $siteName, 'site_name_id' => $siteName, 'site_name_ms' => $siteName];

            $json = SeoMetaBuilder::encodeJsonLd(SeoMetaBuilder::jsonLd($model, $config, $baseUrl, $lang));

            $decoded = json_decode($json, true);
            self::assertSame(JSON_ERROR_NONE, json_last_error());
            self::assertIsArray($decoded);
            self::assertStringNotContainsStringIgnoringCase('</script', $json);

            $blocks = isset($decoded['@type']) ? [$decoded] : $decoded;
            $types  = array_column($blocks, '@type');

            self::assertContains('AutoRental', $types);

            $faqBlocks = array_values(array_filter($blocks, static fn (array $b): bool => $b['@type'] === 'FAQPage'));
            if ($faq === []) {
                self::assertCount(0, $faqBlocks);
            } else {
                self::assertCount(1, $faqBlocks);
                self::assertCount(count($faq), $faqBlocks[0]['mainEntity']);
            }
        });
    }

    /**
     * Validates: Requirements 11.4, 11.5
     */
    public function testCanonicalAndHreflangUrlsAreWellFormedAndComplete(): void
    {
        // Feature: travel-rental-website, Property 23: Canonical and hreflang URLs are well-formed and complete
        $this->limitTo(100)->forAll(
            $this->baseUrls(),
            Generators::elements('en', 'id', 'ms')
        )->then(static function (string $baseUrl, string $lang): void {
            $seo = SeoMetaBuilder::build([], [], $baseUrl, $lang);

            self::assertNotFalse(filter_var($seo['canonical'], FILTER_VALIDATE_URL));
            self::assertMatchesRegularExpression('#^https?://#', $seo['canonical']);

            $alternates = SeoMetaBuilder::alternates($baseUrl);
            self::assertSame($alternates, $seo['alternates']);
            self::assertCount(4, $alternates);

            $byLang = [];
            foreach ($alternates as $alt) {
                self::assertNotFalse(filter_var($alt['href'], FILTER_VALIDATE_URL));
                self::assertMatchesRegularExpression('#^https?://#', $alt['href']);
                self::assertArrayNotHasKey($alt['hreflang'], $byLang);
                $byLang[$alt['hreflang']] = $alt['href'];
            }

            self::assertEqualsCanonicalizing(['en', 'id', 'ms', 'x-default'], array_keys($byLang));
            self::assertSame($byLang['en'], $byLang['x-default']);
        });
    }

    /**
     * Validates: Requirements 11.1, 11.2, 11.6, 11.7
     */
    public function testBuildAlwaysYieldsRequiredKeysAndShortDescription(): void
    {
        // Feature: travel-rental-website, Property 22: SEO builder required output and description limit
        $this->limitTo(100)->forAll(
            $this->models(),
            $this->configs(),
            $this->baseUrls(),
            Generators::elements('en', 'id', 'ms')
        )->then(static function (array $model, array $config, string $baseUrl, string $lang): void {
            $seo = SeoMetaBuilder::build($model, $config, $baseUrl, $lang);

            self::assertNotSame('', trim($seo['title']));
            self::assertLessThanOrEqual(160, mb_strlen($seo['description'], 'UTF-8'));
            self::assertNotSame('', $seo['canonical']);
            self::assertSame('index, follow', $seo['robots']);

            foreach (['og:title', 'og:description', 'og:type', 'og:url', 'og:image', 'og:locale'] as $key) {
                self::assertArrayHasKey($key, $seo['og']);
                self::assertIsString($seo['og'][$key]);
            }
            self::assertNotSame('', $seo['og']['og:title']);
            self::assertNotSame('', $seo['og']['og:image']);
            self::assertNotSame('', $seo['og']['og:locale']);

            foreach (['twitter:card', 'twitter:title', 'twitter:description', 'twitter:image'] as $key) {
                self::assertArrayHasKey($key, $seo['twitter']);
                self::assertIsString($seo['twitter'][$key]);
            }
            self::assertNotSame('', $seo['twitter']['twitter:title']);
            self::assertNotSame('', $seo['twitter']['twitter:image']);
        });
    }
}
