<?php

namespace App\Filters;

use App\Models\AdminUserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Guards admin pages (Req 3.1, 3.6).
 * Unauthenticated or idle (>= 30 min) sessions are redirected to sign-in
 * (or get a 401 JSON for AJAX) before any protected content is rendered.
 */
class AdminAuthFilter implements FilterInterface
{
    public const SESSION_FLAG   = 'admin_logged_in';
    public const LAST_ACTIVITY  = 'admin_last_activity';
    public const USER_ID        = 'admin_user_id';
    public const IDLE_TIMEOUT   = 1800; // seconds
    public const SIGNIN_PATH    = 'signin';

    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $now     = time();

        $loggedIn = $session->get(self::SESSION_FLAG) === true;
        $last     = (int) $session->get(self::LAST_ACTIVITY);

        if ($loggedIn && $last > 0 && ($now - $last) >= self::IDLE_TIMEOUT) {
            $loggedIn = false;
            $session->destroy();
        }

        // An account deleted or deactivated in Master User loses access on its next request.
        if ($loggedIn && ! $this->accountStillActive((int) $session->get(self::USER_ID))) {
            $loggedIn = false;
            $session->destroy();
        }

        if (! $loggedIn) {
            if ($request->isAJAX()) {
                return service('response')
                    ->setStatusCode(401)
                    ->setJSON([
                        'status'  => 401,
                        'message' => 'Sesi berakhir atau belum masuk. Silakan masuk kembali.',
                        'data'    => [],
                        'total'   => 0,
                    ]);
            }

            return redirect()->to(site_url(self::SIGNIN_PATH));
        }

        // Sliding inactivity window
        $session->set(self::LAST_ACTIVITY, $now);

        return null;
    }

    /**
     * Sessions without a user id (started before Master User existed) are left alone;
     * they still expire through the idle timeout.
     */
    protected function accountStillActive(int $userId): bool
    {
        if ($userId <= 0) {
            return true;
        }

        $row = (new AdminUserModel())->select('status')->find($userId);

        return $row !== null && ($row['status'] ?? '') === 'active';
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
