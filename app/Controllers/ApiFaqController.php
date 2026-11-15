<?php

namespace App\Controllers;

use App\Libraries\ProxyResponseMapper;
use App\Libraries\Service;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Proxy for FAQ content. Extends Controller (NOT BaseController) to
 * avoid recursive instantiation; forwards to api/v1/contents-faqs.
 */
class ApiFaqController extends Controller
{
    use ResponseTrait;

    protected $service;

    private const ENDPOINT = 'api/v1/contents-faqs';

    public function __construct()
    {
        $this->service = new Service();

        // Instantiated directly (new ApiFaqController()) from page controllers,
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

    public function apiListActive(): array
    {
        return $this->forward('GET', '/active');
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

    private function payload(): array
    {
        $json = $this->request->getJSON(true);

        return is_array($json) ? $json : (array) $this->request->getPost();
    }

    private function segment($id): string
    {
        return rawurlencode((string) $id);
    }
}
