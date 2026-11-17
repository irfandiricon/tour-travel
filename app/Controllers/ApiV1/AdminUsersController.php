<?php

namespace App\Controllers\ApiV1;

use App\Libraries\AdminSearch;
use App\Filters\AdminAuthFilter;
use App\Libraries\AdminUserValidator;
use App\Models\AdminUserModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;

/**
 * api/v1 admin-users: CMS login accounts (Master User). Extends Controller (not BaseController).
 * Every route is behind the `internalapi` filter. Password hashes never leave this controller.
 */
class AdminUsersController extends Controller
{
    use ResponseTrait;

    protected $adminUserModel;

    /** Columns the admin search looks in (never the password hash). */
    public const SEARCH_COLUMNS = ['username', 'status'];

    public function __construct()
    {
        $this->adminUserModel = new AdminUserModel();
    }

    /** GET admin-users?per_page=&page= (newest first). */
    public function index()
    {
        $perPage = max(1, min(100, (int) ($this->request->getGet('per_page') ?: 10)));
        $page    = max(1, (int) ($this->request->getGet('page') ?: 1));
        $selfId  = $this->currentUserId();

        $search = AdminSearch::term($this->request->getGet('search'));
        $total  = AdminSearch::apply(new AdminUserModel(), $search, self::SEARCH_COLUMNS)->countAllResults();
        $rows   = AdminSearch::apply(new AdminUserModel(), $search, self::SEARCH_COLUMNS)->orderBy('id', 'DESC')->findAll($perPage, ($page - 1) * $perPage);

        $data = array_map(fn (array $row): array => $this->present($row, $selfId), $rows);

        return $this->response->setJSON([
            'status'   => 200,
            'message'  => 'OK',
            'data'     => $data,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
        ]);
    }

    /** GET admin-users/(:num) */
    public function show($id = null)
    {
        $row = $this->adminUserModel->find((int) $id);
        if ($row === null) {
            return $this->notFound();
        }

        return $this->response->setJSON(['status' => 200, 'message' => 'OK', 'data' => $this->present($row, $this->currentUserId()), 'total' => 1]);
    }

    /** POST admin-users */
    public function create()
    {
        $input  = $this->payload();
        $errors = AdminUserValidator::validate($input, true);
        $username = trim((string) ($input['username'] ?? ''));
        if (! isset($errors['username']) && $this->adminUserModel->usernameTaken($username)) {
            $errors['username'] = 'This username is already in use.';
        }
        if ($errors) {
            return $this->invalid($errors);
        }

        $id = $this->adminUserModel->insert([
            'username'        => $username,
            'password_hash'   => password_hash((string) $input['password'], PASSWORD_DEFAULT),
            'status'          => ($input['status'] ?? '') === 'inactive' ? 'inactive' : 'active',
            'failed_attempts' => 0,
        ], true);

        if (! $id) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 500, 'message' => 'Failed to save user', 'data' => [], 'total' => 0]);
        }

        return $this->response->setStatusCode(201)->setJSON([
            'status' => 201, 'message' => 'User created', 'data' => ['id' => (int) $id], 'total' => 1,
        ]);
    }

    /** PUT/POST admin-users/(:num). An empty password keeps the current one. */
    public function update($id = null)
    {
        $id       = (int) $id;
        $existing = $this->adminUserModel->find($id);
        if ($existing === null) {
            return $this->notFound();
        }

        $input = $this->payload();
        if (! array_key_exists('username', $input)) {
            $input['username'] = $existing['username'];
        }
        $errors   = AdminUserValidator::validate($input, false);
        $username = trim((string) $input['username']);
        if (! isset($errors['username']) && $this->adminUserModel->usernameTaken($username, $id)) {
            $errors['username'] = 'This username is already in use.';
        }
        if ($errors) {
            return $this->invalid($errors);
        }

        $isSelf    = $id === $this->currentUserId();
        $wasActive = $existing['status'] === 'active';
        $status    = in_array($input['status'] ?? null, AdminUserValidator::STATUSES, true) ? $input['status'] : $existing['status'];
        $blocked   = AdminUserValidator::protectionError(false, $isSelf, $wasActive, $status === 'active', $this->adminUserModel->countActive());
        if ($blocked !== null) {
            return $this->invalid(['status' => $blocked], 409);
        }

        $record = ['username' => $username, 'status' => $status];
        $password = (string) ($input['password'] ?? '');
        if ($password !== '') {
            // A new password also clears any sign-in lock from earlier failed attempts.
            $record += ['password_hash' => password_hash($password, PASSWORD_DEFAULT), 'failed_attempts' => 0, 'locked_until' => null];
        }
        $this->adminUserModel->update($id, $record);

        if ($isSelf) {
            session()->set('admin_username', $username);
        }

        return $this->response->setJSON(['status' => 200, 'message' => 'User updated', 'data' => ['id' => $id], 'total' => 1]);
    }

    /** DELETE admin-users/(:num) (permanent). */
    public function delete($id = null)
    {
        $id       = (int) $id;
        $existing = $this->adminUserModel->find($id);
        if ($existing === null) {
            return $this->notFound();
        }

        $blocked = AdminUserValidator::protectionError(true, $id === $this->currentUserId(), $existing['status'] === 'active', false, $this->adminUserModel->countActive());
        if ($blocked !== null) {
            return $this->response->setStatusCode(409)->setJSON(['status' => 409, 'message' => $blocked, 'data' => [], 'total' => 0]);
        }

        $this->adminUserModel->delete($id);

        return $this->response->setJSON(['status' => 200, 'message' => 'User deleted', 'data' => ['id' => $id], 'total' => 1]);
    }

    // ------------------------------------------------------------- helpers

    /** Id of the signed-in admin (session forwarded by the proxy), or 0. */
    private function currentUserId(): int
    {
        $session = session();
        $id      = (int) $session->get(AdminAuthFilter::USER_ID);
        if ($id > 0) {
            return $id;
        }

        // Sessions started before admin_user_id existed only know the username.
        $username = trim((string) $session->get('admin_username'));
        $row      = $username !== '' ? $this->adminUserModel->where('username', $username)->first() : null;

        return $row !== null ? (int) $row['id'] : 0;
    }

    /** Public row plus display-only fields for the admin list. */
    private function present(array $row, int $selfId): array
    {
        $out    = AdminUserModel::toPublic($row);
        $isSelf = (int) $row['id'] === $selfId;
        $locked = ! empty($row['locked_until']) && strtotime((string) $row['locked_until']) > time();

        $out['is_self']        = $isSelf;
        $out['can_delete']     = ! $isSelf;
        $out['display_name']   = $row['username'] . ($isSelf ? ' (you)' : '');
        $out['status_label']   = ($row['status'] === 'active' ? 'Active' : 'Inactive') . ($locked ? ' (locked)' : '');
        $out['last_login_label'] = ! empty($row['last_login_at']) ? (string) $row['last_login_at'] : 'Never';

        return $out;
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

    private function notFound()
    {
        return $this->response->setStatusCode(404)->setJSON(['status' => 404, 'message' => 'User not found', 'data' => [], 'total' => 0]);
    }

    private function invalid(array $errors, int $code = 422)
    {
        return $this->response->setStatusCode($code)->setJSON([
            'status'  => $code,
            'message' => ($code === 422 ? 'Validation failed: ' : '') . implode('; ', $errors),
            'errors'  => $errors,
            'data'    => $errors,
            'total'   => 0,
        ]);
    }
}