<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// ---------------------------------------------------------------- Public
$routes->get('/', 'LandingController::index');
$routes->get('lang/(:segment)', 'LanguageController::set/$1');
$routes->get('fleet', 'ListingController::fleet');
$routes->get('gallery', 'ListingController::gallery');
$routes->get('news', 'ListingController::news');
// News detail: news/{id} or news/{id}/{slug}; the slug is only for readers and search engines.
$routes->get('news/(:num)', 'ListingController::newsDetail/$1');
$routes->get('news/(:num)/(:segment)', 'ListingController::newsDetail/$1');
// Information was merged into News & Information: keep old links and bookmarks working.
$routes->addRedirect('information', 'news', 301);
$routes->get('robots.txt', 'SeoController::robots');
$routes->get('sitemap.xml', 'SeoController::sitemap');
$routes->post('track/booking', 'TrackController::booking');

// ------------------------------------------------- Auth (outside admin filter)
$routes->get('signin', 'Admin\AuthController::signin');
$routes->post('signin', 'Admin\AuthController::submit');
$routes->match(['get', 'post'], 'signout', 'Admin\AuthController::signout');

// ------------------------------------------------------------ Admin pages
$routes->group('', ['filter' => 'admin', 'namespace' => 'App\Controllers\Admin'], static function (RouteCollection $routes): void {
    $routes->get('dashboard', 'DashboardController::index');

    $pages = [
        'news'        => 'NewsController',
        'fleet'       => 'FleetController',
        'faq'         => 'FaqController',
        'gallery'     => 'GalleryController',
        'testimonials' => 'TestimonialController',
        'users'       => 'UserController',
    ];

    foreach ($pages as $slug => $controller) {
        $routes->get("admin/{$slug}", "{$controller}::index");
        $routes->get("admin/{$slug}/create", "{$controller}::contentCreate");
        $routes->get("admin/{$slug}/edit/(:num)", "{$controller}::contentUpdate/$1");
    }

    $routes->get('admin/site-config', 'SiteConfigController::index');
});

// -------------------------------------------------- Admin AJAX (proxies)
// The admin JS uses GET for lists and POST for create/update/delete/save.
$routes->group('admin/api', ['filter' => 'admin', 'namespace' => 'App\Controllers'], static function (RouteCollection $routes): void {
    $proxies = [
        'news'        => ['ApiNewsController', ['activate', 'deactivate']],
        'fleet'       => ['ApiFleetController', ['activate', 'deactivate']],
        'faq'         => ['ApiFaqController', []],
        'gallery'     => ['ApiGalleryController', []],
        'testimonials' => ['ApiTestimonialController', []],
        'users'       => ['ApiUserController', []],
    ];

    foreach ($proxies as $slug => [$controller, $toggles]) {
        $routes->get("{$slug}/list", "{$controller}::apiList");
        $routes->post("{$slug}/create", "{$controller}::apiCreate");
        $routes->match(['post', 'put'], "{$slug}/update/(:num)", "{$controller}::apiUpdate/$1");
        $routes->match(['post', 'delete'], "{$slug}/delete/(:num)", "{$controller}::apiDelete/$1");

        foreach ($toggles as $toggle) {
            $routes->match(['post', 'put'], "{$slug}/{$toggle}/(:num)", $controller . '::api' . ucfirst($toggle) . '/$1');
        }
    }

    $routes->post('site-config/save', 'ApiSiteConfigController::apiSave');
    $routes->get('dashboard/summary', 'ApiDashboardController::apiSummaryJson');
});

// ----------------------------------------------------------------- API v1
$routes->group('api/v1', ['namespace' => 'App\Controllers\ApiV1'], static function (RouteCollection $routes): void {
    // Open: login, public reads used server-side by the landing page.
    $routes->post('auth/login', 'AuthController::login');
    $routes->get('site-config', 'SiteConfigController::getConfig');

    $resources = [
        'contents-news'        => ['ContentsNewsController', true, true],
        'contents-fleet'       => ['ContentsFleetController', true, true],
        'contents-faqs'        => ['ContentsFaqsController', false, false],
        'contents-gallery'     => ['ContentsGalleryController', false, true],
        'contents-testimonials' => ['ContentsTestimonialsController', false, true],
    ];

    foreach ($resources as $path => [$controller, $toggles, $multipartUpdate]) {
        // Public read (landing page, no session).
        $routes->get("{$path}/active", "{$controller}::getAllActive");

        // Everything else requires an admin session or a signed internal proxy call.
        $routes->group($path, ['filter' => 'internalapi'], static function (RouteCollection $routes) use ($controller, $toggles, $multipartUpdate): void {
            $routes->get('/', "{$controller}::index");
            $routes->get('(:num)', "{$controller}::show/$1");
            $routes->post('/', "{$controller}::create");
            $routes->put('(:num)', "{$controller}::update/$1");
            $routes->delete('(:num)', "{$controller}::delete/$1");

            if ($multipartUpdate) {
                $routes->post('update/(:num)', "{$controller}::update/$1");
            }

            if ($toggles) {
                $routes->match(['put', 'post'], 'activate/(:num)', "{$controller}::activate/$1");
                $routes->match(['put', 'post'], 'deactivate/(:num)', "{$controller}::deactivate/$1");
            }
        });
    }

    // Master User (CMS login accounts). No public route: every call needs an admin session or a signed proxy call.
    $routes->group('admin-users', ['filter' => 'internalapi'], static function (RouteCollection $routes): void {
        $routes->get('/', 'AdminUsersController::index');
        $routes->get('(:num)', 'AdminUsersController::show/$1');
        $routes->post('/', 'AdminUsersController::create');
        $routes->put('(:num)', 'AdminUsersController::update/$1');
        $routes->delete('(:num)', 'AdminUsersController::delete/$1');
    });

    $routes->post('site-config/save', 'SiteConfigController::save', ['filter' => 'internalapi']);
    $routes->get('dashboard/summary', 'DashboardController::summary', ['filter' => 'internalapi']);
});
