<?php

namespace App\Controllers\Admin;

use App\Controllers\ApiUserController;
use App\Controllers\BaseController;

/**
 * Admin pages for Master User: CMS login accounts (list / create / edit).
 * Data is loaded through the proxy layer; writes happen via AJAX.
 */
class UserController extends BaseController
{
    protected $apiUser;

    public function __construct()
    {
        $this->apiUser = new ApiUserController();
    }

    public function index()
    {
        return view('admin/users/index', [
            'title' => 'Master User',
        ]);
    }

    public function contentCreate()
    {
        return view('admin/users/form', [
            'title' => 'Create User',
            'item'  => null,
        ]);
    }

    public function contentUpdate($id = null)
    {
        $result = $this->apiUser->apiShow($id);

        if (empty($result['data'])) {
            return redirect()->to(base_url('admin/users'))->with('error', $result['message'] ?: 'Not found');
        }

        return view('admin/users/form', [
            'title' => 'Edit User',
            'item'  => $result['data'],
        ]);
    }
}