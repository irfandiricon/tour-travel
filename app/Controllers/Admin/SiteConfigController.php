<?php

namespace App\Controllers\Admin;

use App\Controllers\ApiSiteConfigController;
use App\Controllers\BaseController;

/**
 * Admin page for Site Configuration (section content + WhatsApp number).
 */
class SiteConfigController extends BaseController
{
    protected $apiSiteConfig;

    public function __construct()
    {
        $this->apiSiteConfig = new ApiSiteConfigController();
    }

    public function index()
    {
        $result = $this->apiSiteConfig->apiGetConfig();

        return view('admin/site-config/index', [
            'title'  => 'Site Configuration',
            'config' => $result['data'] ?? [],
        ]);
    }
}