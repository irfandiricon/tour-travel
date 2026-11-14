<?php

use PHPUnit\Framework\TestCase;

/**
 * Smoke test for the redesign's static assets (tokens, stylesheets, local fonts).
 *
 * landing.css and admin.css are only checked for existence and non-empty
 * content; their content is rewritten in later tasks.
 *
 * Validates: Requirements 1.3, 1.5, 14.3
 *
 * @internal
 */
final class TokensAssetsSmokeTest extends TestCase
{
    private function assetPath(string $relative): string
    {
        return dirname(__DIR__, 2) . '/public/assets/' . $relative;
    }

    /**
     * @return array<string,array{0:string}>
     */
    public static function requiredAssets(): array
    {
        return [
            'tokens.css'          => ['css/tokens.css'],
            'landing.css'         => ['css/landing.css'],
            'admin.css'           => ['css/admin.css'],
            'Plus Jakarta Sans'   => ['vendor/fonts/plus-jakarta-sans/PlusJakartaSans-latin-wght.woff2'],
            'Figtree'             => ['vendor/fonts/figtree/Figtree-latin-wght.woff2'],
        ];
    }

    /**
     * @dataProvider requiredAssets
     */
    public function testAssetExistsAndIsNotEmpty(string $relative): void
    {
        $path = $this->assetPath($relative);

        self::assertFileExists($path);
        self::assertGreaterThan(0, filesize($path), $relative . ' must not be empty');
    }

    public function testWoff2FilesHaveWoff2Signature(): void
    {
        foreach ([
            'vendor/fonts/plus-jakarta-sans/PlusJakartaSans-latin-wght.woff2',
            'vendor/fonts/figtree/Figtree-latin-wght.woff2',
        ] as $relative) {
            $head = file_get_contents($this->assetPath($relative), false, null, 0, 4);
            self::assertSame('wOF2', $head, $relative . ' is not a valid woff2 file');
        }
    }

    public function testTokensCssHasNoExternalUrlsOutsideComments(): void
    {
        $css = (string) file_get_contents($this->assetPath('css/tokens.css'));
        // Strip comments; URLs are only tolerated there.
        $code = (string) preg_replace('#/\*.*?\*/#s', '', $css);

        self::assertDoesNotMatchRegularExpression('#https?://#i', $code);
        self::assertStringNotContainsString('cdn.', $css);
        self::assertStringNotContainsString('jsdelivr', $css);
        self::assertStringNotContainsString('fonts.googleapis', $css);
    }

    public function testTokensCssReferencesLocalFontsOnly(): void
    {
        $css = (string) file_get_contents($this->assetPath('css/tokens.css'));

        self::assertSame(2, preg_match_all('#url\("\.\./vendor/fonts/[^"]+\.woff2"\)#', $css));
        self::assertStringContainsString('font-display: swap', $css);
    }
}
