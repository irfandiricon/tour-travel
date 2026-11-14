<?php

use CodeIgniter\Test\CIUnitTestCase;
use Eris\Generators;
use Eris\TestTrait;

/**
 * Property 10: label sets for en/id/ms are complete and non-empty on the
 * landing view and public layout, and unknown languages fall back to en.
 *
 * The label arrays are local to the views, so they are extracted by parsing
 * the literal array source (no app code changes needed).
 *
 * @internal
 */
final class LandingLabelsPropertyTest extends CIUnitTestCase
{
    use TestTrait;

    private const LANGS = ['en', 'id', 'ms'];

    protected function setUp(): void
    {
        parent::setUp();
        helper('url');
    }

    /**
     * @return array<string, array<string, string>>
     */
    private static function extractLabels(string $viewFile, string $varName): array
    {
        $src = file_get_contents(APPPATH . 'Views/' . $viewFile);
        self::assertNotFalse($src);
        $start = strpos($src, '$' . $varName . ' = [');
        self::assertNotFalse($start, "{$varName} not found in {$viewFile}");
        $start = strpos($src, '[', $start);
        // Array literal ends at the first "];" at line start (top-level close).
        $end = strpos($src, "\n];", $start);
        self::assertNotFalse($end, "End of {$varName} not found in {$viewFile}");
        $literal = substr($src, $start, $end - $start + 2);

        $labels = eval('return ' . $literal . ';'); // pure literal array of strings

        self::assertIsArray($labels);

        return $labels;
    }

    /**
     * @param array<string, array<string, string>> $labels
     */
    private static function assertComplete(array $labels, string $where): void
    {
        foreach (self::LANGS as $lang) {
            self::assertArrayHasKey($lang, $labels, "{$where}: missing language {$lang}");
        }
        $enKeys = array_keys($labels['en']);
        sort($enKeys);
        self::assertNotEmpty($enKeys);
        foreach (self::LANGS as $lang) {
            $keys = array_keys($labels[$lang]);
            sort($keys);
            self::assertSame($enKeys, $keys, "{$where}: key set of {$lang} differs from en");
            foreach ($labels[$lang] as $key => $value) {
                self::assertIsString($value);
                self::assertNotSame('', trim($value), "{$where}: {$lang}.{$key} is empty");
            }
        }
    }

    /** @return array<string, mixed> */
    private function viewData(string $lang): array
    {
        return [
            'lang'         => $lang,
            'languages'    => self::LANGS,
            'hero'         => ['title' => 'Hero', 'subtitle' => 'Sub'],
            'about'        => '',
            'galleryTitle' => '',
            'contact'      => ['body' => '', 'email' => '', 'address' => '', 'whatsapp' => null],
            'fleet'        => [],
            'news'         => [],
            'faq'          => [],
            'gallery'      => [],
        ];
    }

    public function testLabelSetsCompleteForAllLanguages(): void
    {
        // Feature: ui-redesign-reference, Property 10: Label tiga bahasa lengkap dan fallback ke en
        $landing = self::extractLabels('landing/index.php', 'labelsByLang');
        $layout  = self::extractLabels('layouts/public.php', 'layoutLabels');

        $keys = [];
        foreach ([['landing', $landing], ['layout', $layout]] as [$name, $labels]) {
            self::assertComplete($labels, $name);
            foreach (array_keys($labels['en']) as $key) {
                $keys[] = [$name, $key];
            }
        }

        $this->limitTo(100)->forAll(
            Generators::elements(...$keys),
            Generators::elements(...self::LANGS)
        )->then(static function (array $pair, string $lang) use ($landing, $layout): void {
            [$name, $key] = $pair;
            $labels       = $name === 'landing' ? $landing : $layout;

            self::assertArrayHasKey($key, $labels[$lang]);
            self::assertNotSame('', trim($labels[$lang][$key]));
        });
    }

    public function testUnknownLanguageFallsBackToEnglish(): void
    {
        // Feature: ui-redesign-reference, Property 10: Label tiga bahasa lengkap dan fallback ke en
        $landing = self::extractLabels('landing/index.php', 'labelsByLang');
        $layout  = self::extractLabels('layouts/public.php', 'layoutLabels');
        $emptyFleet = esc($landing['en']['emptyFleet']);
        $footer     = esc($layout['en']['footer']);

        $this->limitTo(100)->forAll(
            Generators::suchThat(
                static fn (string $s): bool => ! in_array($s, self::LANGS, true),
                Generators::string()
            )
        )->then(function (string $unknown) use ($emptyFleet, $footer): void {
            $html = view('landing/index', $this->viewData($unknown), ['saveData' => false]);

            self::assertStringContainsString('<html lang="en"', $html);
            self::assertStringContainsString($emptyFleet, $html);
            self::assertStringContainsString($footer, $html);
        });
    }

    public function testSupportedLanguagesRenderTheirOwnLabels(): void
    {
        // Feature: ui-redesign-reference, Property 10: Label tiga bahasa lengkap dan fallback ke en
        $landing = self::extractLabels('landing/index.php', 'labelsByLang');
        $layout  = self::extractLabels('layouts/public.php', 'layoutLabels');

        $this->limitTo(100)->forAll(
            Generators::elements(...self::LANGS)
        )->then(function (string $lang) use ($landing, $layout): void {
            $html = view('landing/index', $this->viewData($lang), ['saveData' => false]);

            self::assertStringContainsString('<html lang="' . $lang . '"', $html);
            self::assertStringContainsString(esc($landing[$lang]['emptyFleet']), $html);
            self::assertStringContainsString(esc($layout[$lang]['footer']), $html);
        });
    }
}
