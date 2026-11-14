<?php

namespace App\Controllers;

use App\Libraries\LanguageResolver;
use CodeIgniter\HTTP\RedirectResponse;

class LanguageController extends PublicBaseController
{
    /** Pages the language switcher may return to (anything else goes to the landing page). */
    private const RETURN_PAGES = ['fleet', 'gallery', 'news'];

    public function set($lang = null): RedirectResponse
    {
        $current = session('lang');
        $before  = is_string($current) ? ['lang' => $current] : [];
        $after   = LanguageResolver::persist($before, is_string($lang) ? $lang : null);

        if (isset($after['lang']) && $after['lang'] !== ($before['lang'] ?? null)) {
            session()->set('lang', $after['lang']);
        }

        $return = service('request')->getGet('return');

        return redirect()->to(is_string($return) && (in_array($return, self::RETURN_PAGES, true) || preg_match('#^news/\d{1,10}$#', $return) === 1) ? site_url($return) : '/');
    }
}