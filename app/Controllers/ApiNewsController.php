<?php

namespace App\Controllers;

use App\Libraries\ProxyResponseMapper;
use App\Libraries\Service;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Proxy for News content. Extends Controller (NOT BaseController) to
 * avoid recursive instantiation; forwards to api/v1/contents-news.
 */
class ApiNewsController extends Controller
{
    use ResponseTrait;

    protected $service;

    private const ENDPOINT = 'api/v1/contents-news';

    /** Text fields of the news form; `image_remove` is the "remove current picture" checkbox. */
    private const TEXT_FIELDS = [
        'title_en', 'title_id', 'title_ms',
        'body_en', 'body_id', 'body_ms',
        'publish_date', 'status', 'image_remove',
    ];
    public function __construct()
    {
        $this->service = new Service();

        // Instantiated directly (new ApiNewsController()) from page controllers,
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
        return $this->response->setJSON($this->forwardUpload('POST', ''));
    }

    public function apiUpdate($id = null): ResponseInterface
    {
        // Multipart cannot be parsed on PUT by PHP: use the POST update route.
        return $this->response->setJSON($this->forwardUpload('POST', '/update/' . $this->segment($id)));
    }

    public function apiDelete($id = null): ResponseInterface
    {
        return $this->response->setJSON($this->forward('DELETE', '/' . $this->segment($id)));
    }

    public function apiActivate($id = null): ResponseInterface
    {
        return $this->response->setJSON($this->forward('PUT', '/activate/' . $this->segment($id)));
    }

    public function apiDeactivate($id = null): ResponseInterface
    {
        return $this->response->setJSON($this->forward('PUT', '/deactivate/' . $this->segment($id)));
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

    /**
     * Multipart forward: the form fields plus the uploaded `image` as a CURLFile (the temp file
     * is PHP's own and is removed at the end of the request). A file PHP rejected is reported
     * instead of being silently dropped.
     *
     * @return array{status: int, message: string, data: mixed, total: int}
     */
    private function forwardUpload(string $method, string $suffix): array
    {
        $payload = [];
        foreach (self::TEXT_FIELDS as $field) {
            $value = $this->request->getPost($field);
            if ($value !== null && is_scalar($value)) {
                $payload[$field] = (string) $value;
            }
        }

        $file = $this->request->getFile('image');
        if ($file !== null && ($failure = ProxyResponseMapper::uploadFailure($file->getError())) !== null) {
            return $failure;
        }
        if ($file !== null && $file->isValid() && ! $file->hasMoved() && is_file($file->getTempName())) {
            $payload['image'] = new \CURLFile($file->getTempName(), $file->getClientMimeType(), $file->getClientName());
        }

        $result = $this->service->ApiRequesUploadWithHeaderToken(base_url(self::ENDPOINT) . $suffix, $payload, $method);

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
