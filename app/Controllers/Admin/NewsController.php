<?php

namespace App\Controllers\Admin;

use App\Controllers\ApiNewsController;
use App\Controllers\BaseController;

/**
 * Admin pages for News & Information content (list / create / edit).
 * Data is loaded through the proxy layer; writes happen via AJAX.
 */
class NewsController extends BaseController
{
    protected $apiNews;

    public function __construct()
    {
        $this->apiNews = new ApiNewsController();
    }

    public function index()
    {
        return view('admin/news/index', [
            'title' => 'News & Information',
        ]);
    }

    public function contentCreate()
    {
        return view('admin/news/form', [
            'title' => 'Create News & Information',
            'item'  => null,
        ]);
    }

    public function contentUpdate($id = null)
    {
        $result = $this->apiNews->apiShow($id);

        if (empty($result['data'])) {
            return redirect()->to(base_url('admin/news'))->with('error', $result['message'] ?: 'Not found');
        }

        return view('admin/news/form', [
            'title' => 'Edit News & Information',
            'item'  => $result['data'],
        ]);
    }
}