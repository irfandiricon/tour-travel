<?php

namespace App\Controllers;

use App\Controllers\Concerns\BuildsPublicContent;
use App\Libraries\LanguageResolver;
use App\Libraries\SeoMetaBuilder;
use App\Libraries\TestimonialValidator;
use App\Libraries\VisitorTracker;
use App\Libraries\WhatsAppLinkBuilder;

class LandingController extends PublicBaseController
{
    use BuildsPublicContent;

    /**
     * How many items each section shows on the landing page. The rest is on the
     * dedicated page (see ListingController), reached via the "View more" button.
     */
    public const SECTION_LIMITS = ['fleet' => 3, 'gallery' => 6, 'news' => 9, 'testimonials' => 9];

    /** Width / height from which the About picture counts as a banner and is stacked above the text. */
    public const WIDE_IMAGE_RATIO = 1.6;

    protected $apiFleet;
    protected $apiNews;
    protected $apiFaq;
    protected $apiGallery;
    protected $apiTestimonial;
    protected $apiSiteConfig;
    protected $visitorTracker;

    public function __construct()
    {
        $this->visitorTracker = new VisitorTracker();
        $this->apiFleet       = new ApiFleetController();
        $this->apiNews        = new ApiNewsController();
        $this->apiFaq         = new ApiFaqController();
        $this->apiGallery     = new ApiGalleryController();
        $this->apiTestimonial = new ApiTestimonialController();
        $this->apiSiteConfig  = new ApiSiteConfigController();
    }

    public function index()
    {
        $lang = $this->lang;

        $config = $this->section($this->apiSiteConfig, 'apiGetConfig', true);
        $waNum  = isset($config['whatsapp_number']) && is_string($config['whatsapp_number']) ? $config['whatsapp_number'] : '';

        $fleetAll       = $this->buildFleetItems($this->section($this->apiFleet, 'apiListActive'), $waNum);
        $newsAll        = $this->buildNewsItems($this->section($this->apiNews, 'apiListActive'));

        $faq = [];
        foreach ($this->section($this->apiFaq, 'apiListActive') as $row) {
            $faq[] = [
                'question' => LanguageResolver::resolve($this->translations($row, 'question'), $lang),
                'answer'   => LanguageResolver::resolve($this->translations($row, 'answer'), $lang),
            ];
        }

        $galleryRows = $this->section($this->apiGallery, 'apiListActive');
        $galleryAll  = $this->buildGalleryItems($galleryRows);

        // Hero carousel uses the full gallery; the gallery section only shows a preview.
        $heroSlides = $this->heroSlides($galleryRows, $galleryAll, $this->configText($config, 'hero_title', $lang));

        $limits = self::SECTION_LIMITS;

        $testimonialRows = $this->section($this->apiTestimonial, 'apiListActive');
        $testimonials    = $this->buildTestimonialItems($testimonialRows);

        $viewModel = [
            'lang'        => $lang,
            'languages'   => LanguageResolver::SUPPORTED,
            'brand'       => $this->configText($config, 'site_name', $lang),
            'hero'        => [
                'title'    => $this->configText($config, 'hero_title', $lang),
                'subtitle' => $this->configText($config, 'hero_subtitle', $lang),
            ],
            'about'       => $this->configText($config, 'about_body', $lang),
            'aboutTitle'  => $this->configText($config, 'about_title', $lang),
            'aboutImage'  => trim((string) ($config['about_image'] ?? '')),
            'aboutImageWide' => $this->isWideImage(trim((string) ($config['about_image'] ?? ''))),
            'galleryTitle' => $this->configText($config, 'gallery_title', $lang),
            'contact'     => [
                'body'    => $this->configText($config, 'contact_body', $lang),
                'email'   => (string) ($config['contact_email'] ?? ''),
                'address' => (string) ($config['contact_address'] ?? ''),
                'whatsapp' => WhatsAppLinkBuilder::build($waNum, ''),
            ],
            'fleet'       => array_slice($fleetAll, 0, $limits['fleet']),
            'news'        => array_slice($newsAll, 0, $limits['news']),
            'faq'         => $faq,
            'gallery'     => array_slice($galleryAll, 0, $limits['gallery']),
            'testimonials' => array_slice($testimonials, 0, $limits['testimonials']),
            // Average over every published review, not just the ones shown.
            'ratingSummary' => TestimonialValidator::summary($testimonials),
            // Real totals (the lists above are previews) and the pages the "View more" buttons open.
            'totals'      => [
                'fleet'       => count($fleetAll),
                'gallery'     => count($galleryAll),
                'news'        => count($newsAll),
            ],
            'pages'       => [
                'fleet'       => site_url('fleet'),
                'gallery'     => site_url('gallery'),
                'news'        => site_url('news'),
            ],
            'heroSlides'  => $heroSlides,
            'trackBookingUrl' => base_url('track/booking'),
        ];

        $viewModel['seo'] = SeoMetaBuilder::build($viewModel, $config, base_url(), $lang);

        $this->trackVisit();

        return view('landing/index', $viewModel, ['saveData' => false]);
    }

    /**
     * Published reviews with a valid 1-5 rating (newest first, as the API returns them).
     * The review is shown in the visitor's language, falling back to English when not translated.
     *
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array<int, array{name: string, photo: string, rating: int, review: string}>
     */
    private function buildTestimonialItems(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $rating = TestimonialValidator::rating(is_scalar($row['rating'] ?? null) ? (string) $row['rating'] : null);
            $name   = trim((string) ($row['customer_name'] ?? ''));
            $review = trim(LanguageResolver::resolve($this->translations($row, 'review'), $this->lang));
            if (($row['status'] ?? 'published') !== 'published' || $rating === null || $name === '' || $review === '') {
                continue;
            }
            $out[] = ['name' => $name, 'photo' => trim((string) ($row['photo_path'] ?? '')), 'rating' => $rating, 'review' => $review];
        }

        return $out;
    }

    /**
     * True when a stored image is a wide banner (width / height >= WIDE_IMAGE_RATIO, e.g. 16:9).
     * Such pictures are shown at full width above the text. Ordinary photos (3:2, 4:3, square,\n     * portrait) sit beside the text at their own proportions.
     * Missing files, remote URLs and unreadable images count as "not wide".
     */
    private function isWideImage(string $path): bool
    {
        if ($path === '' || preg_match('#^https?://#i', $path) === 1) {
            return false;
        }

        $file = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($path, '/'));
        if (! is_file($file)) {
            return false;
        }

        $size = @getimagesize($file);

        return is_array($size) && $size[1] > 0 && ($size[0] / $size[1]) >= self::WIDE_IMAGE_RATIO;
    }

    /**
     * First 5 published gallery items (by sort order) with a resolved image URL and localized alt text.
     *
     * @param array<int, array<string, mixed>> $rows    raw gallery rows
     * @param array<int, array<string, mixed>> $gallery localized gallery entries (same order as $rows)
     *
     * @return array<int, array{image: string, alt: string}>
     */
    private function heroSlides(array $rows, array $gallery, string $heroTitle): array
    {
        $indexed = [];
        foreach (array_values($rows) as $i => $row) {
            $indexed[] = [$i, $row];
        }
        usort($indexed, static function (array $a, array $b): int {
            $cmp = (int) ($a[1]['sort_order'] ?? 0) <=> (int) ($b[1]['sort_order'] ?? 0);

            return $cmp !== 0 ? $cmp : $a[0] <=> $b[0];
        });

        $slides = [];
        foreach ($indexed as [$i, $row]) {
            if (($row['status'] ?? 'published') !== 'published') {
                continue;
            }
            $path = trim((string) ($row['image_path'] ?? ''));
            if ($path === '') {
                continue;
            }
            $url     = preg_match('#^https?://#i', $path) === 1 ? $path : base_url(ltrim($path, '/'));
            $caption = trim((string) ($gallery[$i]['caption'] ?? ''));
            $slides[] = ['image' => $url, 'alt' => $caption !== '' ? $caption : $heroTitle];
            if (count($slides) === 5) {
                break;
            }
        }

        return $slides;
    }
}