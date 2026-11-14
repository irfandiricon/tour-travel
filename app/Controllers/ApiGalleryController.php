<?php

namespace App\Controllers;

use App\Libraries\ProxyResponseMapper;
use App\Libraries\Service;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Proxy for Gallery content. Extends Controller (NOT BaseController) to avoid
 * recursive instantiation; forwards to api/v1/contents-gallery.
 *
 * Image-bearing create/update are forwarded as multipart (CURLFile). Updates are
 * sent as POST to .../update/{id} because PHP does not parse multipart on PUT.
 */
class ApiGalleryController extends Controller
{
    use ResponseTrait;

    protected $service;

    private const ENDPOINT = 'api/v1/contents-gallery';

    private const TEXT_FIELDS = [
        'caption_en', 'caption_id', 'caption_ms',
        'sort_order', 'status',
    ];

    public function __construct()
    {
        $this->service = new Service();

        // Instantiated directly (new ApiGalleryController()) from page controllers,
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

    // ------------------------------------------------- PHP-consumed (array)

    public function apiListActive(): array
    {
        return $this->forward('GET', '/active');
    }

    // ------------------------------------------------------------- helpers

    /**
     * @return array{status: int, message: string, data: mixed, total: int}
     */
    private function forward(string $method, string $suffix = ''): array
    {
        $result = $this->service->ApiRequestWithHeaderToken(base_url(self::ENDPOINT) . $suffix, '', $method);

        return ProxyResponseMapper::map($result);
    }

    /**
     * Multipart forward: POST fields plus the uploaded `image` as a CURLFile.
     * The CURLFile points at PHP's own upload temp file (removed by PHP at
     * request end), so no extra temp copies are created.
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
            $payload['image'] = new \CURLFile(
                $file->getTempName(),
                $file->getClientMimeType(),
                $file->getClientName()
            );
        }

        $result = $this->service->ApiRequesUploadWithHeaderToken(base_url(self::ENDPOINT) . $suffix, $payload, $method);

        return ProxyResponseMapper::map($result);
    }

    private function segment($id): string
    {
        return rawurlencode((string) $id);
    }
}
