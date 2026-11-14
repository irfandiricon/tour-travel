<?php

use CodeIgniter\Test\CIUnitTestCase;
use Eris\Generators;
use Eris\TestTrait;

/**
 * @internal
 */
final class LandingEscapingPropertyTest extends CIUnitTestCase
{
    use TestTrait;

    /** Penanda unik tanpa spasi agar tetap utuh walau kata terakhir disorot. */
    private const MARKER = '<i/x="PWN">';

    private const MARKER_ESCAPED = '&lt;i/x=&quot;PWN&quot;&gt;';

    protected function setUp(): void
    {
        parent::setUp();
        helper('url');
    }

    /**
     * String berisi <script>, ", ', & dan penanda tag/atribut di akhir.
     */
    private function hostile(): \Eris\Generator
    {
        $fragments = Generators::elements(
            'Avanza', 'Bali', 'été', '日本語', ' ', '<script>alert(1)</script>', '<script>',
            '"', "'", '&', '&amp;', '<', '>', '" onmouseover="PWN', "' onerror='PWN"
        );

        return Generators::map(
            static fn (array $parts): string => implode('', $parts) . self::MARKER,
            Generators::seq($fragments)
        );
    }

    private function render(string $lang, string $s, string $wa): string
    {
        $data = [
            'lang'         => $lang,
            'languages'    => ['en', 'id', 'ms'],
            'hero'         => ['title' => $s, 'subtitle' => $s],
            'about'        => $s,
            'galleryTitle' => $s,
            'contact'      => ['body' => $s, 'email' => $s, 'address' => $s, 'whatsapp' => $wa],
            'fleet'        => [['id' => 1, 'name' => $s, 'description' => $s, 'image' => 'x.jpg', 'whatsapp' => $wa]],
            'news'         => [['title' => $s, 'body' => $s, 'publish_date' => '2025-01-01 00:00:00']],
            'faq'          => [['question' => $s, 'answer' => $s]],
            'gallery'      => [['image' => 'g.jpg', 'caption' => $s]],
            'heroSlides'   => [['image' => 'http://localhost/s.jpg', 'alt' => $s]],
        ];

        return view('landing/index', $data, ['saveData' => false]);
    }

    /**
     * Validates: Requirements 5.15, 3.10
     */
    public function testAllLandingDataTextIsEscaped(): void
    {
        // Feature: ui-redesign-reference, Property 9: Semua teks data landing ter-escape
        $this->limitTo(100)->forAll(
            $this->hostile(),
            Generators::elements('en', 'id', 'ms')
        )->then(function (string $s, string $lang): void {
            $wa   = 'https://wa.me/628123456789?text=' . $s;
            $html = $this->render($lang, $s, $wa);

            // Bentuk mentah tidak boleh muncul sebagai tag/atribut.
            $this->assertStringNotContainsString(self::MARKER, $html);
            $this->assertStringNotContainsString('<script>alert(1)', $html);
            $this->assertStringNotContainsString('" onmouseover="PWN', $html);
            $this->assertStringNotContainsString('<script>' . self::MARKER, $html);

            // Bentuk ter-escape hadir.
            $this->assertStringContainsString(self::MARKER_ESCAPED, $html);
        });
    }
}
