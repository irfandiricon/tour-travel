<?php

namespace App\Controllers\Admin;

use App\Controllers\ApiDashboardController;
use App\Controllers\BaseController;

/**
 * Admin dashboard (protected by the `admin` route filter).
 */
class DashboardController extends BaseController
{
    protected $apiDashboard;

    public function __construct()
    {
        $this->apiDashboard = new ApiDashboardController();
    }

    public function index(): string
    {
        return view('admin/dashboard', [
            'username' => (string) session()->get('admin_username'),
            'summary'  => $this->apiDashboard->apiSummary(),
        ]);
    }
}
