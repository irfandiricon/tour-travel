<?php

use App\Controllers\ApiV1\ContentsNewsController;
use App\Controllers\LandingController;
use App\Controllers\ListingController;
use App\Controllers\PublicBaseController;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Optional picture on News & Information items: card markup, public pages, admin form, API wiring.
 *
 * @internal
 */
final class NewsImageTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper(['url', 'ui', 'form']);
        service('request')->setGlobal('get', []);
    }

    private function d(string $html): string
    {
        return html_entity_decode($html, ENT_QUOTES | ENT_HTML5);
    }

    /** @param array<string,mixed> $o */
    private function card(array $o = [], bool $featured = false, array $extra = []): string
    {
        $n = array_merge(['title' => 'Promo', 'body' => 'Body', 'publish_date' => '2025-03-01 10:00:00', 'image' => 'uploads/news/a.png'], $o);

        return ui_partial('news_card', ['n' => $n, 'featured' => $featured] + $extra);
    }

    // ------------------------------------------------------------------- card

    public function testCardWithPictureShowsItOnTop(): void
    {
        $html = $this->d($this->card());

        $this->assertStringContainsString('<div class="news-card__media">', $html);
        $this->assertMatchesRegularExpression('#<img src="https?://[^"]*/uploads/news/a\.png" alt="Promo" loading="lazy">#', $html);
        $this->assertLessThan(strpos($html, 'news-card__title'), strpos($html, 'news-card__media'), 'picture above the title');
        $this->assertStringNotContainsString('data-lightbox', $html, 'the card opens the detail page, not the popup');
    }

    public function testCardWithoutPictureIsUnchanged(): void
    {
        foreach (['', '   '] as $image) {
            $html = $this->card(['image' => $image]);

            $this->assertStringNotContainsString('news-card__media', $html);
            $this->assertStringNotContainsString('data-lightbox', $html);
            $this->assertStringContainsString('<h3 class="h5 news-card__title">Promo</h3>', $html);
        }
    }

    public function testCardWithoutAnImageKeyStillRenders(): void
    {
        $html = ui_partial('news_card', ['n' => ['title' => 'T', 'body' => 'B', 'publish_date' => '']]);

        $this->assertStringNotContainsString('news-card__media', $html);
        $this->assertStringContainsString('class="news-card"', $html);
    }

    public function testRootClassIsAlwaysNewsCard(): void
    {
        $this->assertStringContainsString('<article class="news-card">', $this->card([], true));
        $this->assertStringContainsString('<article class="news-card">', $this->card([], false));
        $this->assertStringNotContainsString('news-feature', $this->card([], true));
    }

    public function testReadMoreLabelIsLocalizedAndAbsoluteUrlsAreKept(): void
    {
        $html = $this->d($this->card(['image' => 'https://cdn.example.com/p.jpg', 'url' => 'http://x.test/news/1/promo'], false, ['t' => ['readMore' => 'Baca selengkapnya']]));

        $this->assertStringContainsString('<img src="https://cdn.example.com/p.jpg"', $html);
        $this->assertStringContainsString('Baca selengkapnya', $html);
    }
    public function testHostileTitleAndPathAreEscaped(): void
    {
        $html = $this->card(['title' => '"><script>alert(1)</script>', 'image' => 'x.png" onerror="alert(1)']);

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('" onerror="', $html);
    }

    // ------------------------------------------------------------ public pages

    private function stub(array $data): object
    {
        return new class ($data) {
            public function __construct(private array $d)
            {
            }

            public function __call($n, $a)
            {
                return ['status' => 200, 'message' => '', 'data' => $this->d, 'total' => count($this->d)];
            }
        };
    }

    private function build(string $class, array $news, string $lang = 'en')
    {
        $ref = new ReflectionClass($class);
        $c   = $ref->newInstanceWithoutConstructor();
        foreach (['apiSiteConfig' => ['site_name_en' => 'Kimigo Travel'], 'apiNews' => $news, 'apiFleet' => [], 'apiGallery' => [], 'apiFaq' => []] as $prop => $data) {
            if ($ref->hasProperty($prop)) {
                $p = $ref->getProperty($prop);
                $p->setAccessible(true);
                $p->setValue($c, $this->stub($data));
            }
        }
        $base = new ReflectionClass(PublicBaseController::class);
        foreach (['lang' => $lang, 'request' => service('request')] as $prop => $value) {
            $p = $base->getProperty($prop);
            $p->setAccessible(true);
            $p->setValue($c, $value);
        }

        return $c;
    }

    /** @return list<array<string,mixed>> */
    private function newsRows(): array
    {
        return [
            ['status' => 'published', 'publish_date' => '2024-02-01 00:00:00', 'title_en' => 'With picture', 'body_en' => 'B', 'image_path' => 'uploads/news/a.png'],
            ['status' => 'published', 'publish_date' => '2024-01-01 00:00:00', 'title_en' => 'No picture', 'body_en' => 'B', 'image_path' => null],
        ];
    }

    public function testLandingShowsThePictureOfANewsItem(): void
    {
        $html = $this->d($this->build(LandingController::class, $this->newsRows())->index());

        $this->assertSame(1, substr_count($html, 'news-card__media">'), 'only the item that has a picture');
        $this->assertMatchesRegularExpression('#<img src="https?://[^"]*/uploads/news/a\.png"#', $html);
    }

    public function testNewsPageShowsThePictureAndUsesItForSharing(): void
    {
        $html = $this->d($this->build(ListingController::class, $this->newsRows())->news());

        $this->assertSame(1, substr_count($html, 'news-card__media">'));
        $this->assertMatchesRegularExpression('#<meta property="og:image" content="https?://[^"]*/uploads/news/a\.png">#', $html);
    }

    public function testNewsPageWithoutPicturesFallsBackToTheDefaultSharingImage(): void
    {
        $rows = $this->newsRows();
        $rows[0]['image_path'] = '';
        $html = $this->d($this->build(ListingController::class, $rows)->news());

        $this->assertStringNotContainsString('news-card__media', $html);
        $this->assertMatchesRegularExpression('#<meta property="og:image" content="https?://[^"]*assets/img/og-default\.jpg">#', $html);
    }

    // ------------------------------------------------------------- admin form

    private function adminForm(array $item): string
    {
        session()->set('admin_logged_in', true);

        return view('admin/news/form', ['title' => 'Edit News & Information', 'item' => $item], ['saveData' => false]);
    }

    public function testAdminFormHasPictureInputAndRemoveOptionOnlyWhenThereIsOne(): void
    {
        $with = $this->adminForm(['id' => 5, 'image_path' => 'uploads/news/a.png']);
        $this->assertStringContainsString('type="file" id="image" name="image"', $with);
        $this->assertStringContainsString('enctype="multipart/form-data"', $with);
        $this->assertStringContainsString('name="image_remove"', $with);
        $this->assertStringContainsString('Remove current image', $with);
        $this->assertMatchesRegularExpression('#<img src="https?://[^"]*/uploads/news/a\.png"[^>]*alt="Current image"#', $this->d($with));

        $without = $this->adminForm(['id' => 5]);
        $this->assertStringContainsString('name="image"', $without);
        $this->assertStringNotContainsString('image_remove', $without);
    }

    public function testFleetFormStillHasNoRemoveOption(): void
    {
        session()->set('admin_logged_in', true);
        $html = view('admin/fleet/form', ['title' => 'Edit Fleet', 'item' => ['id' => 1, 'image_path' => 'uploads/fleet/a.png']], ['saveData' => false]);

        $this->assertStringContainsString('name="image"', $html);
        $this->assertStringNotContainsString('image_remove', $html);
    }

    // ------------------------------------------------------------------- wiring

    public function testNewsUpdateAcceptsMultipartPostAndStoresInItsOwnFolder(): void
    {
        // The third flag of the resource entry turns on the POST .../update/(:num) route that multipart
        // uploads need (PHP does not parse multipart bodies on PUT). Verified live and with `spark routes`.
        $this->assertMatchesRegularExpression(
            "/'contents-news'\s*=>\s*\['ContentsNewsController', true, true\]/",
            (string) file_get_contents(APPPATH . 'Config/Routes.php')
        );
        $this->assertSame('uploads/news', ContentsNewsController::UPLOAD_DIR);
        $this->assertContains('image_remove', (new ReflectionClassConstant(\App\Controllers\ApiNewsController::class, 'TEXT_FIELDS'))->getValue());
    }

    public function testValidationErrorsAreSentUnderTheErrorsKeyForInlineDisplay(): void
    {
        $ref = new ReflectionClass(ContentsNewsController::class);
        $c   = $ref->newInstanceWithoutConstructor();
        $rp  = new ReflectionProperty(\CodeIgniter\Controller::class, 'response');
        $rp->setAccessible(true);
        $rp->setValue($c, service('response'));

        $m = $ref->getMethod('invalid');
        $m->setAccessible(true);
        $res  = $m->invoke($c, ['image' => 'Allowed image types are JPEG, PNG, and WebP.', 'title_en' => 'required']);
        $body = json_decode($res->getBody(), true);

        $this->assertSame(422, $res->getStatusCode());
        $this->assertSame(['image', 'title_en'], array_keys($body['errors']));
        $this->assertSame($body['errors'], $body['data'], 'kept under data for existing clients');
    }

    public function testNewsPictureColumnIsAllowedInTheModelAndSchema(): void
    {
        $model = new ReflectionProperty(\App\Models\NewsModel::class, 'allowedFields');
        $model->setAccessible(true);
        $fields = $model->getValue((new ReflectionClass(\App\Models\NewsModel::class))->newInstanceWithoutConstructor());

        $this->assertContains('image_path', $fields);
        $this->assertMatchesRegularExpression('/CREATE TABLE IF NOT EXISTS `c_news`.*?`image_path`\s+VARCHAR\(500\) NULL/s', (string) file_get_contents(ROOTPATH . 'database/schema.sql'));
    }
}