<?php

namespace App\Controllers\Admin;

use App\Controllers\ApiTestimonialController;
use App\Controllers\BaseController;

/**
 * Admin pages for customer reviews: star rating, name, photo and review (list / create / edit).
 * Data is loaded through the proxy layer; writes happen via AJAX.
 */
class TestimonialController extends BaseController
{
    protected $apiTestimonial;

    public function __construct()
    {
        $this->apiTestimonial = new ApiTestimonialController();
    }

    public function index()
    {
        return view('admin/testimonials/index', [
            'title' => 'Customer Reviews',
        ]);
    }

    public function contentCreate()
    {
        return view('admin/testimonials/form', [
            'title' => 'Create Review',
            'item'  => null,
        ]);
    }

    public function contentUpdate($id = null)
    {
        $result = $this->apiTestimonial->apiShow($id);

        if (empty($result['data'])) {
            return redirect()->to(base_url('admin/testimonials'))->with('error', $result['message'] ?: 'Not found');
        }

        return view('admin/testimonials/form', [
            'title' => 'Edit Review',
            'item'  => $result['data'],
        ]);
    }
}