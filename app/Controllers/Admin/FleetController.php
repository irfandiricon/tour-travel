<?php

namespace App\Controllers\Admin;

use App\Controllers\ApiFleetController;
use App\Controllers\BaseController;

/**
 * Admin pages for Fleet content (list / create / edit).
 * Data is loaded through the proxy layer; writes happen via AJAX.
 */
class FleetController extends BaseController
{
    protected $apiFleet;

    public function __construct()
    {
        $this->apiFleet = new ApiFleetController();
    }

    public function index()
    {
        return view('admin/fleet/index', [
            'title' => 'Fleet',
        ]);
    }

    public function contentCreate()
    {
        return view('admin/fleet/form', [
            'title' => 'Create Fleet',
            'item'  => null,
        ]);
    }

    public function contentUpdate($id = null)
    {
        $result = $this->apiFleet->apiShow($id);

        if (empty($result['data'])) {
            return redirect()->to(base_url('admin/fleet'))->with('error', $result['message'] ?: 'Not found');
        }

        return view('admin/fleet/form', [
            'title' => 'Edit Fleet',
            'item'  => $result['data'],
        ]);
    }
}