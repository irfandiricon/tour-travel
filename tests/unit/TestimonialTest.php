<?php

use App\Controllers\ApiV1\ContentsTestimonialsController;
use App\Controllers\LandingController;
use App\Controllers\PublicBaseController;
use App\Libraries\TestimonialValidator;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Customer Reviews: validation, stars/average, admin form + menu, landing section. DB-free.
 *
 * @internal
 */
final class TestimonialTest extends CIUnitTestCase
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

    // ------------------------------------------------------------ validation

    public function testValidReviewPasses(): void
    {
        $this->assertSame([], TestimonialValidator::validate(['customer_name' => 'Budi', 'rating' => '5', 'review_en' => 'Great']));
        $this->assertSame([], TestimonialValidator::validate(['customer_name' => 'Budi', 'rating' => 1, 'review_en' => 'Ok', 'review_id' => '', 'review_ms' => null]));
    }

    public function testRequiredFieldsAndLimits(): void
    {
        $e = TestimonialValidator::validate([]);
        $this->assertSame(['customer_name', 'rating', 'review_en'], array_keys($e));
        $this->assertSame('Review (EN) is required.', $e['review_en']);

        $e = TestimonialValidator::validate(['customer_name' => str_repeat('a', 101), 'rating' => '3', 'review_en' => str_repeat('b', 501), 'review_id' => str_repeat('c', 501), 'review_ms' => str_repeat('d', 501)]);
        $this->assertSame('Customer name must be at most 100 characters.', $e['customer_name']);
        $this->assertSame('Review (EN) must be at most 500 characters.', $e['review_en']);
        $this->assertSame('Review (ID) must be at most 500 characters.', $e['review_id']);
        $this->assertSame('Review (MS) must be at most 500 characters.', $e['review_ms']);
        $this->assertSame([], TestimonialValidator::validate(['customer_name' => str_repeat('a', 100), 'rating' => '3', 'review_en' => str_repeat('b', 500), 'review_id' => str_repeat('c', 500)]));
    }

    /** @return array<string, array{0: mixed}> */
    public static function badRatings(): array
    {
        return ['zero' => ['0'], 'six' => ['6'], 'half' => ['4.5'], 'text' => ['abc'], 'empty' => [''], 'null' => [null], 'bool' => [true], 'array' => [[5]], 'negative' => ['-1']];
    }

    /** @dataProvider badRatings */
    public function testOnlyWholeStarsFromOneToFiveAreAccepted($rating): void
    {
        $this->assertNull(TestimonialValidator::rating($rating));
        $this->assertArrayHasKey('rating', TestimonialValidator::validate(['customer_name' => 'A', 'rating' => $rating, 'review_en' => 'B']));
    }

    public function testStarsAndSummary(): void
    {
        $this->assertSame("\u{2605}\u{2605}\u{2605}\u{2605}\u{2606}", TestimonialValidator::stars(4));
        $this->assertSame(str_repeat("\u{2606}", 5), TestimonialValidator::stars('x'));

        $this->assertSame(['average' => 4.7, 'count' => 3], TestimonialValidator::summary([['rating' => 5], ['rating' => '4'], ['rating' => 5], ['rating' => 'bad']]));
        $this->assertNull(TestimonialValidator::summary([]));
    }

    public function testPhotosHaveTheirOwnFolderAndRoutesAreWired(): void
    {
        $this->assertSame('uploads/testimonials', ContentsTestimonialsController::UPLOAD_DIR);

        $routes = (string) file_get_contents(APPPATH . 'Config/Routes.php');
        $this->assertStringContainsString("'contents-testimonials' => ['ContentsTestimonialsController', false, true],", $routes);
        $this->assertStringContainsString("'testimonials' => 'TestimonialController',", $routes);
        $this->assertStringContainsString("'testimonials' => ['ApiTestimonialController', []],", $routes);

        $src = (string) file_get_contents(APPPATH . 'Controllers/ApiV1/ContentsTestimonialsController.php');
        $this->assertStringContainsString('$this->imageHandler->errors($file->getMimeType()', $src, 'type checked on the file content');
    }

    // ------------------------------------------------------------ admin views

    private function admin(string $view, array $data): string
    {
        // Nested view() calls keep their data in the shared renderer; start each render clean.
        \Config\Services::resetSingle('renderer');
        session()->set('admin_logged_in', true);
        try {
            return $this->d(view($view, $data, ['saveData' => false]));
        } finally {
            session()->remove('admin_logged_in');
        }
    }

    public function testMenuHasCustomerReviews(): void
    {
        $this->assertMatchesRegularExpression('#href="[^"]*/admin/testimonials">\s*<i class="bi bi-star-half" aria-hidden="true"></i>\s*<span>Customer Reviews</span>#', $this->admin('admin/layout', ['title' => 'x']));
    }

    public function testListColumns(): void
    {
        $html = $this->admin('admin/testimonials/index', ['title' => 'Customer Reviews']);

        foreach (['customer_name' => 'Customer', 'stars' => 'Rating', 'status' => 'Status'] as $k => $label) {
            $this->assertStringContainsString('data-key="' . $k . '">' . $label . '</th>', $html);
        }
    }

    public function testCreateFormHasNamePhotoStarsAndReview(): void
    {
        $html = $this->admin('admin/testimonials/form', ['title' => 'Create Review', 'item' => null]);

        $this->assertStringContainsString('id="customer_name" name="customer_name" required maxlength="100"', $html);
        $this->assertSame(5, substr_count($html, 'type="radio" id="rating-'));
        $this->assertStringContainsString('value="5" checked', $html, 'new reviews start at 5 stars');
        $this->assertStringContainsString('<legend class="form-label fs-6 mb-1">Rating', $html);
        foreach (['en', 'id', 'ms'] as $lang) {
            $this->assertMatchesRegularExpression('#<textarea class="form-control" id="review_' . $lang . '" name="review_' . $lang . '" rows="5"\s+maxlength="500"#', $html);
        }
        $this->assertMatchesRegularExpression('#name="review_en"[^>]*required aria-required="true"#', $html, 'English is required');
        $this->assertDoesNotMatchRegularExpression('#name="review_id"[^>]*required#', $html, 'Indonesian is optional');
        $this->assertStringContainsString('English (EN) is the default language', $html);
        $this->assertStringContainsString('Profile photo (JPEG, PNG or WebP, max 5 MB)', $html);
        $this->assertStringContainsString('name="image"', $html);
        $this->assertStringNotContainsString('image_remove', $html, 'nothing to remove yet');
        foreach (['customer_name', 'rating', 'review_en', 'review_id', 'review_ms', 'image', 'status'] as $field) {
            $this->assertStringContainsString('data-error-for="' . $field . '"', $html);
        }
    }

    public function testEditFormIsPrefilled(): void
    {
        $html = $this->admin('admin/testimonials/form', ['title' => 'Edit Review', 'item' => [
            'id' => 7, 'customer_name' => 'Sari', 'rating' => 3, 'review_en' => 'Comfortable', 'review_id' => 'Nyaman', 'photo_path' => 'uploads/testimonials/a.png', 'status' => 'draft',
        ]]);

        $this->assertStringContainsString('data-id="7"', $html);
        $this->assertStringContainsString('value="Sari"', $html);
        $this->assertStringContainsString('value="3" checked', $html);
        $this->assertSame(1, substr_count($html, ' checked'), 'only one star selected');
        $this->assertStringContainsString('>Comfortable</textarea>', $html);
        $this->assertStringContainsString('>Nyaman</textarea>', $html);
        $this->assertMatchesRegularExpression('#<img src="https?://[^"]*/uploads/testimonials/a\.png"#', $html);
        $this->assertStringContainsString('name="image_remove"', $html);
        $this->assertStringContainsString('<option value="draft" selected>', $html);
    }

    public function testOtherImageFieldsKeepTheirLabel(): void
    {
        $html = $this->admin('admin/gallery/form', ['title' => 'Create Gallery', 'item' => null]);

        $this->assertStringContainsString('Image (JPEG, PNG or WebP, max 5 MB)', $html);
    }

    // --------------------------------------------------------------- landing

    private function landing(array $reviews, string $lang = 'en'): string
    {
        $stub = static fn (array $d): object => new class ($d) {
            public function __construct(private array $d)
            {
            }

            public function __call($n, $a)
            {
                return ['status' => 200, 'message' => '', 'data' => $this->d, 'total' => count($this->d)];
            }
        };
        $ref = new ReflectionClass(LandingController::class);
        $c   = $ref->newInstanceWithoutConstructor();
        foreach (['apiSiteConfig' => ['site_name_en' => 'Kimigo Travel'], 'apiTestimonial' => $reviews, 'apiNews' => [], 'apiFleet' => [], 'apiGallery' => [], 'apiFaq' => []] as $prop => $data) {
            $p = $ref->getProperty($prop);
            $p->setAccessible(true);
            $p->setValue($c, $stub($data));
        }
        $base = new ReflectionClass(PublicBaseController::class);
        foreach (['lang' => $lang, 'request' => service('request')] as $prop => $value) {
            $p = $base->getProperty($prop);
            $p->setAccessible(true);
            $p->setValue($c, $value);
        }

        return $this->d($c->index());
    }

    private function review(array $o = []): array
    {
        return array_merge(['id' => 1, 'customer_name' => 'Budi Santoso', 'rating' => 5, 'review_en' => 'Friendly driver', 'review_id' => 'Sopir ramah', 'review_ms' => null, 'photo_path' => null, 'status' => 'published'], $o);
    }

    public function testLandingShowsReviewsWithStarsNameAndPhotoOrInitials(): void
    {
        $html = $this->landing([
            $this->review(['rating' => 4, 'photo_path' => 'uploads/testimonials/b.png', 'customer_name' => 'Ani']),
            $this->review(),
        ]);

        $this->assertStringContainsString('<section id="testimonials" class="section section--peach" aria-labelledby="testimonials-title">', $html);
        $this->assertStringContainsString('<h2 id="testimonials-title">What Our Customers Say</h2>', $html);
        $this->assertSame(2, substr_count($html, '<figure class="testi-card">'));
        $this->assertStringContainsString('role="img" aria-label="Rated 4 out of 5"', $html);
        $this->assertMatchesRegularExpression('#<img class="testi-card__avatar" src="https?://[^"]*/uploads/testimonials/b\.png" alt=""#', $html);
        $this->assertStringContainsString('testi-card__avatar--initials" aria-hidden="true">BS</span>', $html);
        $this->assertStringContainsString('<p>Friendly driver</p>', $html);
        $this->assertStringContainsString('4.5 out of 5 from 2 reviews', $html);
        $this->assertStringContainsString('aria-controls="testimonials-rail" aria-label="Next"', $html);
        $this->assertLessThan(strpos($html, 'id="faq"'), strpos($html, 'id="testimonials"'), 'placed before the FAQ');
    }

    public function testSummaryIsLocalizedAndSingular(): void
    {
        $this->assertStringContainsString('5,0 dari 5 berdasarkan 1 ulasan', $this->landing([$this->review()], 'id'));
        $this->assertStringContainsString('5,0 daripada 5 berdasarkan 1 ulasan', $this->landing([$this->review()], 'ms'));
        $this->assertStringContainsString('5.0 out of 5 from 1 review', $this->landing([$this->review()]));
        $this->assertStringContainsString('aria-label="Rating 5 dari 5"', $this->landing([$this->review()], 'id'));
    }

    public function testLandingLimitsTheCarouselButAveragesEveryReview(): void
    {
        $rows = [];
        for ($i = 1; $i <= 12; $i++) {
            $rows[] = $this->review(['id' => $i, 'rating' => $i <= 3 ? 2 : 5]);
        }
        $html = $this->landing($rows);

        $this->assertSame(LandingController::SECTION_LIMITS['testimonials'], substr_count($html, '<figure class="testi-card">'));
        $this->assertStringContainsString('4.3 out of 5 from 12 reviews', $html);
    }

    public function testInvalidOrHiddenReviewsAreSkippedAndAnEmptySectionIsHidden(): void
    {
        $html = $this->landing([
            $this->review(['status' => 'draft']),
            $this->review(['rating' => 9]),
            $this->review(['customer_name' => '  ']),
            $this->review(['review_en' => '', 'review_id' => '']),
        ]);

        $this->assertStringNotContainsString('id="testimonials"', $html);
        $this->assertStringNotContainsString('testi-card', $html);
    }

    public function testNameAndReviewAreEscaped(): void
    {
        $html = ui_partial('testimonial_card', ['r' => ['name' => '<script>x</script>', 'photo' => '', 'rating' => 5, 'review' => '<img src=x onerror=alert(1)>']]);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
    }

    public function testReviewFollowsTheVisitorLanguageWithEnglishFallback(): void
    {
        $rows = [$this->review()];

        $this->assertStringContainsString('<p>Sopir ramah</p>', $this->landing($rows, 'id'));
        $this->assertStringContainsString('<p>Friendly driver</p>', $this->landing($rows, 'ms'), 'no Malay text: English is shown');
        $this->assertStringContainsString('<p>Friendly driver</p>', $this->landing($rows, 'en'));
    }

    public function testMigrationKeepsTheExistingReviewAsEnglish(): void
    {
        $src = (string) file_get_contents(APPPATH . 'Database/Migrations/2025-03-04-000001_AddLanguagesToTestimonialReview.php');

        $this->assertStringContainsString('CHANGE `review` `review_en` TEXT NOT NULL', $src, 'rename keeps the data');
        $this->assertStringContainsString('ADD `review_id` TEXT NULL AFTER `review_en`', $src);
        $this->assertStringContainsString('ADD `review_ms` TEXT NULL AFTER `review_id`', $src);
        $this->assertMatchesRegularExpression('/`review_en`\s+TEXT NOT NULL,\s+`review_id`\s+TEXT NULL,\s+`review_ms`\s+TEXT NULL,/', (string) file_get_contents(ROOTPATH . 'database/schema.sql'));
        $this->assertSame(['en', 'id', 'ms'], TestimonialValidator::REVIEW_LANGS);
    }

    public function testReviewsAutoScrollAndLoopWithAPauseButton(): void
    {
        $html = $this->landing([$this->review(), $this->review(['id' => 2])]);

        $this->assertStringContainsString('<div class="news-carousel news-carousel--testi" data-news-carousel data-loop data-autoplay="5000">', $html);
        $this->assertMatchesRegularExpression('#<button type="button" class="carousel-toggle" data-carousel-toggle aria-pressed="false" aria-controls="testimonials-rail"\s+data-label-pause="Pause reviews" data-label-play="Play reviews"#', $html);
        $this->assertStringContainsString('data-label-pause="Jeda ulasan" data-label-play="Putar ulasan"', $this->landing([$this->review()], 'id'));
    }

    public function testNewsCarouselDoesNotAutoScroll(): void
    {
        $view = (string) file_get_contents(APPPATH . 'Views/landing/index.php');

        $this->assertStringContainsString('<div class="news-carousel" data-news-carousel>', $view, 'news: manual only');
        $this->assertSame(1, substr_count($view, 'data-autoplay='));
    }

    public function testCarouselScriptLoopsPausesAndRespectsReducedMotion(): void
    {
        $js = (string) file_get_contents(FCPATH . 'assets/js/landing.js');

        $this->assertStringContainsString("var loop = root.hasAttribute('data-loop');", $js);
        $this->assertStringContainsString("rail.scrollTo({ left: 0, behavior: behavior() });", $js, 'after the last card: back to the first');
        $this->assertStringContainsString("rail.scrollTo({ left: maxScroll(), behavior: behavior() });", $js, 'before the first card: to the last');
        $this->assertStringContainsString('if (stopped || hovered || focused || document.hidden || fits()) {', $js);
        $this->assertStringContainsString('var stopped = reduce;', $js, 'no autoplay with prefers-reduced-motion');
        $this->assertStringContainsString("root.addEventListener('mouseenter'", $js);
        $this->assertStringContainsString("root.addEventListener('focusin'", $js);
        $this->assertStringContainsString("toggle.setAttribute('aria-pressed', stopped ? 'true' : 'false');", $js);
    }
}