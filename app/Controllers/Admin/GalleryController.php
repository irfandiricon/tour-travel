<?php

namespace App\Controllers\Admin;

use App\Controllers\ApiGalleryController;
use App\Controllers\BaseController;

/**
 * Admin pages for Gallery items (list / create / edit).
 * The gallery proxy has no apiShow, so edit resolves the item from apiListActive.
 */
class GalleryController extends BaseController
{
    protected $apiGallery;

    public function __construct()
    {
        $this->apiGallery = new ApiGalleryController();
    }

    public function index()
    {
        $result = $this->apiGallery->apiListActive();

        return view('admin/gallery/index', [
            'title' => 'Gallery',
            'items' => $result['data'] ?? [],
        ]);
    }

    public function contentCreate()
    {
        return view('admin/gallery/form', [
            'title' => 'Create Gallery Item',
            'item'  => null,
        ]);
    }

    public function contentUpdate($id = null)
    {
        $result = $this->apiGallery->apiListActive();
        $item   = null;

        foreach ((array) ($result['data'] ?? []) as $row) {
            $row = (array) $row;
            if ((string) ($row['id'] ?? '') === (string) $id) {
                $item = $row;
                break;
            }
        }

        if ($item === null) {
            return redirect()->to(base_url('admin/gallery'))->with('error', 'Not found');
        }

        return view('admin/gallery/form', [
            'title' => 'Edit Gallery Item',
            'item'  => $item,
        ]);
    }
}