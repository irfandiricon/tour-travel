<?php

namespace App\Controllers\Admin;

use App\Controllers\ApiFaqController;
use App\Controllers\BaseController;

/**
 * Admin pages for Faq content (list / create / edit).
 * Data is loaded through the proxy layer; writes happen via AJAX.
 */
class FaqController extends BaseController
{
    protected $apiFaq;

    public function __construct()
    {
        $this->apiFaq = new ApiFaqController();
    }

    public function index()
    {
        return view('admin/faq/index', [
            'title' => 'Faq',
        ]);
    }

    public function contentCreate()
    {
        return view('admin/faq/form', [
            'title' => 'Create Faq',
            'item'  => null,
        ]);
    }

    public function contentUpdate($id = null)
    {
        $result = $this->apiFaq->apiShow($id);

        if (empty($result['data'])) {
            return redirect()->to(base_url('admin/faq'))->with('error', $result['message'] ?: 'Not found');
        }

        return view('admin/faq/form', [
            'title' => 'Edit Faq',
            'item'  => $result['data'],
        ]);
    }
}