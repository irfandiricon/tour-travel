<?php

namespace App\Controllers\Admin;

use App\Controllers\ApiAuthController;
use App\Controllers\BaseController;
use App\Filters\AdminAuthFilter;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Admin sign-in / sign-out (Req 3.2, 3.3, 3.4).
 * The admin auth guard is the `admin` route filter (not BaseController), so the
 * sign-in page stays reachable when routed outside that filter.
 */
class AuthController extends BaseController
{
    public const GENERIC_ERROR = 'Invalid username or password';

    protected $apiAuth;

    public function __construct()
    {
        $this->apiAuth = new ApiAuthController();
    }

    /** GET signin */
    public function signin(): RedirectResponse|string
    {
        if (session()->get(AdminAuthFilter::SESSION_FLAG) === true) {
            return redirect()->to(site_url('dashboard'));
        }

        return view('admin/signin', [
            'error' => session()->getFlashdata('error'),
        ]);
    }

    /** POST signin */
    public function submit(): RedirectResponse
    {
        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');

        $result = $this->apiAuth->apiLogin(['username' => $username, 'password' => $password]);
        $status = (int) ($result['status'] ?? 0);

        if ($status === 200) {
            $session = session();
            $session->regenerate();
            $session->set([
                AdminAuthFilter::SESSION_FLAG  => true,
                AdminAuthFilter::LAST_ACTIVITY => time(),
                AdminAuthFilter::USER_ID       => (int) ($result['data']['id'] ?? 0),
                'admin_username'               => $result['data']['username'] ?? $username,
            ]);

            return redirect()->to(site_url('dashboard'));
        }

        // 423 carries the locked message; everything else is the generic error.
        $message = $status === 423 && ! empty($result['message'])
            ? (string) $result['message']
            : self::GENERIC_ERROR;

        return redirect()->to(site_url(AdminAuthFilter::SIGNIN_PATH))->with('error', $message);
    }

    /** GET signout */
    public function signout(): RedirectResponse
    {
        session()->destroy();

        return redirect()->to(site_url(AdminAuthFilter::SIGNIN_PATH));
    }
}
