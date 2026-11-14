<?php

namespace App\Controllers;

use App\Libraries\ProxyResponseMapper;
use App\Libraries\Service;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Proxy for admin sign-in. Extends Controller (NOT BaseController) to avoid
 * recursive instantiation; forwards to api/v1/auth/login.
 *
 * Upstream error statuses (e.g. 401 invalid credentials, 423 locked) are kept
 * by ProxyResponseMapper along with the generic upstream message.
 */
class ApiAuthController extends Controller
{
    use ResponseTrait;

    protected $service;

    private const ENDPOINT = 'api/v1/auth/login';

    public function __construct()
    {
        $this->service = new Service();

        $this->request  = service('request');
        $this->response = service('response');
    }

    // ---------------------------------------------------------------- AJAX

    public function apiLoginJson(): ResponseInterface
    {
        return $this->response->setJSON($this->apiLogin());
    }

    // ------------------------------------------------- PHP-consumed (array)

    /**
     * @param array|null $credentials ['email'/'username', 'password']; defaults to the request payload
     *
     * @return array{status: int, message: string, data: mixed, total: int}
     */
    public function apiLogin(?array $credentials = null): array
    {
        $body   = $credentials ?? $this->payload();
        $result = $this->service->ApiRequestWithHeaderToken(base_url(self::ENDPOINT), json_encode($body), 'POST');

        return ProxyResponseMapper::map($result);
    }

    // ------------------------------------------------------------- helpers

    private function payload(): array
    {
        $json = $this->request->getJSON(true);

        return is_array($json) ? $json : (array) $this->request->getPost();
    }
}
