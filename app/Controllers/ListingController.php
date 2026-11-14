<?php

namespace App\Controllers;

use App\Controllers\Concerns\BuildsPublicContent;
use App\Libraries\LanguageResolver;
use App\Libraries\Paginator;
use App\Libraries\SeoMetaBuilder;
use App\Libraries\VisitorTracker;
use App\Libraries\WhatsAppLinkBuilder;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * The full Fleet, Gallery and News & Information pages. The landing page only shows
 * a preview of each (see LandingController::SECTION_LIMITS) and links here.
 */
class ListingController extends PublicBaseController
{
    use BuildsPublicContent;

    /** Items per page for each listing. */
    public const PER_PAGE = ['fleet' => 9, 'gallery' => 12, 'news' => 9];

    /** Other items listed under a news detail page. */
    public const RELATED_NEWS = 3;

    protected $apiFleet;
    protected $apiNews;
    protected $apiGallery;
    protected $apiSiteConfig;
    protected $visitorTracker;

    public function __construct()
    {
        $this->visitorTracker = new VisitorTracker();
        $this->apiFleet       = new ApiFleetController();
        $this->apiNews        = new ApiNewsController();
        $this->apiGallery     = new ApiGalleryController();
        $this->apiSiteConfig  = new ApiSiteConfigController();
    }

    public function fleet()
    {
        return $this->show('fleet');
    }

    public function gallery()
    {
        return $this->show('gallery');
    }

    public function news()
    {
        return $this->show('news');
    }
    /**
     * One News & Information item at news/{id}[/{slug}]. Drafts, future-dated and unknown
     * items are 404 because only published items reach buildNewsItems().
     */
    public function newsDetail($id = null)
    {
        $id = (int) $id;
        if ($id <= 0) {
            throw PageNotFoundException::forPageNotFound();
        }

        helper('ui');

        $lang   = $this->lang;
        $labels = ui_page_labels($lang);
        $config = $this->section($this->apiSiteConfig, 'apiGetConfig', true);
        $waNum  = isset($config['whatsapp_number']) && is_string($config['whatsapp_number']) ? $config['whatsapp_number'] : '';

        $item   = null;
        $others = [];
        foreach ($this->buildNewsItems($this->section($this->apiNews, 'apiListActive')) as $n) {
            if ($item === null && $n['id'] === $id) {
                $item = $n;
            } elseif (count($others) < self::RELATED_NEWS) {
                $others[] = $n;
            }
        }
        if ($item === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $brand = $this->configText($config, 'site_name', $lang);
        if ($brand === '') {
            $brand = $labels['brand'];
        }

        $newsTitle = $labels['pages']['news']['title'];
        $seo       = SeoMetaBuilder::buildPage(
            ['brand' => $brand, 'gallery' => $item['image'] !== '' ? [['image' => $item['image']]] : []],
            $config,
            base_url(),
            $lang,
            $item['path'],
            $item['title'],
            $item['excerpt'],
            $labels['home']
        );
        $seo['og']['og:type'] = 'article';
        $seo['jsonld'] = [
            [
                '@context'        => 'https://schema.org',
                '@type'           => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => $labels['home'], 'item' => SeoMetaBuilder::urlFor(base_url(), $lang)],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => $newsTitle, 'item' => SeoMetaBuilder::urlFor(base_url(), $lang, 'news')],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => $item['title'], 'item' => $seo['canonical']],
                ],
            ],
            array_filter([
                '@context'      => 'https://schema.org',
                '@type'         => 'NewsArticle',
                'headline'      => mb_substr($item['title'], 0, 110),
                'description'   => $seo['description'],
                'image'         => $seo['og']['og:image'],
                'datePublished' => ($ts = strtotime((string) $item['publish_date'])) !== false ? date('c', $ts) : null,
                'url'           => $seo['canonical'],
                'publisher'     => ['@type' => 'Organization', 'name' => $brand],
            ], static fn ($v): bool => $v !== null),
        ];

        $this->trackVisit();

        return view('pages/news_detail', [
            'lang'            => $lang,
            'brand'           => $brand,
            'item'            => $item,
            'others'          => $others,
            'newsTitle'       => $newsTitle,
            'labels'          => $labels,
            'contact'         => ['whatsapp' => WhatsAppLinkBuilder::build($waNum, '')],
            'trackBookingUrl' => base_url('track/booking'),
            'seo'             => $seo,
        ], ['saveData' => false]);
    }

    private function show(string $kind)
    {
        if (! isset(self::PER_PAGE[$kind])) {
            throw PageNotFoundException::forPageNotFound();
        }

        helper('ui');

        $lang   = $this->lang;
        $labels = ui_page_labels($lang);
        $config = $this->section($this->apiSiteConfig, 'apiGetConfig', true);
        $waNum  = isset($config['whatsapp_number']) && is_string($config['whatsapp_number']) ? $config['whatsapp_number'] : '';

        $items = match ($kind) {
            'fleet'       => $this->buildFleetItems($this->section($this->apiFleet, 'apiListActive'), $waNum),
            'gallery'     => $this->buildGalleryItems($this->section($this->apiGallery, 'apiListActive')),
            default       => $this->buildNewsItems($this->section($this->apiNews, 'apiListActive')),
        };

        // Items with a real picture, used for the social sharing image (never the fleet placeholder).
        $ogItems = [];
        foreach ($items as $item) {
            $image = (string) ($item['image'] ?? '');
            if ($image !== '' && $image !== self::FLEET_PLACEHOLDER) {
                $ogItems[] = ['image' => $image];
            }
        }
        $total   = count($items);
        $perPage = self::PER_PAGE[$kind];
        $pages   = max(1, (int) ceil($total / $perPage));
        $page    = min(max(1, (int) $this->request->getGet('page')), $pages);
        $slice   = Paginator::paginate($items, $page, $perPage)['data'];

        $brand = $this->configText($config, 'site_name', $lang);
        if ($brand === '') {
            $brand = $labels['brand'];
        }

        $meta  = $labels['pages'][$kind];
        $title = $meta['title'];
        if ($kind === 'gallery') {
            $custom = $this->configText($config, 'gallery_title', $lang);
            $title  = $custom !== '' ? $custom : $title;
        }

        $viewModel = [
            'lang'      => $lang,
            'brand'     => $brand,
            'kind'      => $kind,
            'title'     => $title,
            'subtitle'  => $meta['sub'],
            'emptyText' => $meta['empty'],
            'items'     => $slice,
            'pagination' => ['page' => $page, 'pages' => $pages, 'total' => $total, 'perPage' => $perPage],
            'labels'    => $labels,
            'contact'   => ['whatsapp' => WhatsAppLinkBuilder::build($waNum, '')],
            'trackBookingUrl' => base_url('track/booking'),
            'seo'       => SeoMetaBuilder::buildPage(
                ['brand' => $brand, 'gallery' => $ogItems],
                $config,
                base_url(),
                $lang,
                $kind,
                $title,
                $meta['desc'],
                $labels['home'],
                $page
            ),
        ];

        $this->trackVisit();

        return view('pages/listing', $viewModel, ['saveData' => false]);
    }
}