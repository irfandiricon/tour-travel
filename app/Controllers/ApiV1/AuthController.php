<?php

namespace App\Controllers\ApiV1;

use App\Libraries\LoginThrottle;
use App\Models\AdminUserModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;

/**
 * api/v1 auth endpoints (Req 3.2, 3.3, 3.5).
 * Extends Controller (not BaseController).
 */
class AuthController extends Controller
{
    use ResponseTrait;

    public const GENERIC_ERROR = 'Invalid username or password';

    protected $adminUserModel;

    public function __construct()
    {
        $this->adminUserModel = new AdminUserModel();
    }

    /** POST auth/login */
    public function login()
    {
        $input    = $this->payload();
        $username = is_string($input['username'] ?? null) ? trim($input['username']) : '';
        $password = is_string($input['password'] ?? null) ? $input['password'] : '';

        $now  = time();
        $user = $username !== '' ? $this->adminUserModel->findActiveByUsername($username) : null;

        // Unknown/inactive account: burn comparable time, return the same generic error.
        if ($user === null) {
            password_verify($password, '$2y$10$usesomesillystringforsalt$.invalidinvalidinvalidinvalidinvali');

            return $this->fail();
        }

        // Locked: do not evaluate the password or extend the lock.
        if (LoginThrottle::isLocked($user['locked_until'] ?? null, $now)) {
            return $this->response->setStatusCode(423)->setJSON([
                'status'  => 423,
                'message' => 'Too many failed attempts. Please try again later.',
                'data'    => [],
                'total'   => 0,
            ]);
        }

        if (! password_verify($password, (string) $user['password_hash'])) {
            $lastFailure = ! empty($user['updated_at']) ? strtotime($user['updated_at']) : null;
            $state       = LoginThrottle::registerFailure(
                (int) ($user['failed_attempts'] ?? 0),
                $lastFailure === false ? null : $lastFailure,
                $now
            );
            $this->adminUserModel->update((int) $user['id'], $state);

            return $this->fail();
        }

        $this->adminUserModel->update((int) $user['id'], array_merge(
            LoginThrottle::registerSuccess(),
            ['last_login_at' => date('Y-m-d H:i:s', $now)]
        ));

        return $this->response->setJSON([
            'status'  => 200,
            'message' => 'OK',
            'data'    => ['id' => (int) $user['id'], 'username' => $user['username']],
            'total'   => 1,
        ]);
    }

    private function fail()
    {
        return $this->response->setStatusCode(401)->setJSON([
            'status'  => 401,
            'message' => self::GENERIC_ERROR,
            'data'    => [],
            'total'   => 0,
        ]);
    }

    private function payload(): array
    {
        // getJSON() throws on a non-JSON body (the admin forms post FormData), so only parse JSON requests.
        $json = str_contains(strtolower($this->request->getHeaderLine('Content-Type')), 'json') ? $this->request->getJSON(true) : null;
        if (is_array($json) && $json) {
            return $json;
        }

        return $this->request->getPost() ?: [];
    }
}
