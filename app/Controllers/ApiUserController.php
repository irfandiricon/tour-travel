<?php

namespace App\Controllers;

use App\Libraries\ProxyResponseMapper;
use App\Libraries\Service;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Proxy for Master User (CMS login accounts). Extends Controller (NOT BaseController) to
 * avoid recursive instantiation; forwards to api/v1/admin-users.
 */
class ApiUserController extends Controller
{
    use ResponseTrait;

    protected $service;

    private const ENDPOINT = 'api/v1/admin-users';

    public function __construct()
    {
        $this->service = new Service();

        // Instantiated directly (new ApiUserController()) from page controllers,
        // so initController() never runs; bind request/response here.
        $this->request  = service('request');
        $this->response = service('response');
    }

    // ---------------------------------------------------------------- AJAX

    public function apiList(): ResponseInterface
    {
        $query = http_build_query([
            'per_page' => $this->request->getGet('per_page') ?? 10,
            'page'     => $this->request->getGet('page') ?? 1,
            'search'   => is_string($this->request->getGet('search')) ? $this->request->getGet('search') : '',
        ]);

        return $this->response->setJSON($this->forward('GET', '?' . $query));
    }

    public function apiCreate(): ResponseInterface
    {
        return $this->response->setJSON($this->forward('POST', '', $this->payload()));
    }

    public function apiUpdate($id = null): ResponseInterface
    {
        return $this->response->setJSON($this->forward('PUT', '/' . $this->segment($id), $this->payload()));
    }

    public function apiDelete($id = null): ResponseInterface
    {
        return $this->response->setJSON($this->forward('DELETE', '/' . $this->segment($id)));
    }


    // ------------------------------------------------- PHP-consumed (array)

    public function apiShow($id = null): array
    {
        return $this->forward('GET', '/' . $this->segment($id));
    }


    // ------------------------------------------------------------- helpers

    /**
     * @return array{status: int, message: string, data: mixed, total: int}
     */
    private function forward(string $method, string $suffix = '', array $body = []): array
    {
        $url    = base_url(self::ENDPOINT) . $suffix;
        $result = $this->service->ApiRequestWithHeaderToken($url, $body === [] ? '' : json_encode($body), $method);

        return ProxyResponseMapper::map($result);
    }

    /** Only the account fields are forwarded (not the CSRF token or anything else in the form). */
    private function payload(): array
    {
        // getJSON() throws on a non-JSON body, so only read it for JSON requests (the admin form posts FormData).
        $isJson = str_contains(strtolower($this->request->getHeaderLine('Content-Type')), 'json');
        $json   = $isJson ? $this->request->getJSON(true) : null;
        $input  = is_array($json) ? $json : (array) $this->request->getPost();

        return array_intersect_key($input, array_flip(['username', 'password', 'password_confirm', 'status']));
    }

    private function segment($id): string
    {
        return rawurlencode((string) $id);
    }
}
