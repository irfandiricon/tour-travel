<?php

namespace App\Controllers;

use App\Libraries\ProxyResponseMapper;
use App\Libraries\Service;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Proxy for Site Configuration. Extends Controller (NOT BaseController) to
 * avoid recursive instantiation; forwards to api/v1/site-config.
 */
class ApiSiteConfigController extends Controller
{
    use ResponseTrait;

    protected $service;

    private const ENDPOINT = 'api/v1/site-config';

    public function __construct()
    {
        $this->service = new Service();

        // Instantiated directly from page controllers, so initController()
        // never runs; bind request/response here.
        $this->request  = service('request');
        $this->response = service('response');
    }

    // ---------------------------------------------------------------- AJAX

    public function apiSave(): ResponseInterface
    {
        return $this->response->setJSON($this->forwardSave());
    }

    // ------------------------------------------------- PHP-consumed (array)

    public function apiGetConfig(): array
    {
        return $this->forward('GET');
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

    /** File inputs of the site configuration form (each may also be sent as a plain path string). */
    private const FILE_FIELDS = ['og_image', 'about_image'];

    /**
     * Forward the save as multipart so the optional image uploads (`og_image`, `about_image`)
     * reach api/v1 as CURLFiles alongside the text fields. A file PHP rejected (for example
     * larger than upload_max_filesize) is reported instead of being silently dropped.
     *
     * @return array{status: int, message: string, data: mixed, total: int}
     */
    private function forwardSave(): array
    {
        $input   = $this->payload();
        $payload = [];
        foreach ($input as $field => $value) {
            if (is_string($field) && ! in_array($field, self::FILE_FIELDS, true) && (is_scalar($value) || $value === null)) {
                $payload[$field] = (string) $value;
            }
        }

        foreach (self::FILE_FIELDS as $field) {
            $file = $this->request->getFile($field);

            if ($file !== null && ($failure = ProxyResponseMapper::uploadFailure($file->getError(), $field)) !== null) {
                return $failure;
            }

            if ($file !== null && $file->isValid() && ! $file->hasMoved() && is_file($file->getTempName())) {
                $payload[$field] = new \CURLFile($file->getTempName(), $file->getClientMimeType(), $file->getClientName());
            } elseif (isset($input[$field]) && is_scalar($input[$field])) {
                // Path supplied as text instead of an upload.
                $payload[$field] = (string) $input[$field];
            }
        }

        $result = $this->service->ApiRequesUploadWithHeaderToken(base_url(self::ENDPOINT) . '/save', $payload, 'POST');

        return ProxyResponseMapper::map($result);
    }
    private function payload(): array
    {
        $post = $this->request->getPost();
        if (is_array($post) && $post !== []) {
            return $post;
        }

        try {
            $json = $this->request->getJSON(true);
        } catch (\Throwable $e) {
            $json = null;
        }

        return is_array($json) ? $json : [];
    }
}