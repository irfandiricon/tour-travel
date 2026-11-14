<?php

namespace App\Controllers;

use App\Controllers\ApiV1\DashboardController as DashboardApi;
use App\Libraries\ProxyResponseMapper;
use App\Libraries\Service;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Proxy for the dashboard summary. Extends Controller (NOT BaseController) to
 * avoid recursive instantiation; forwards to api/v1/dashboard/summary.
 * On any upstream failure it falls back to a zero-shaped summary with a
 * non-200 status, so the view can always render.
 */
class ApiDashboardController extends Controller
{
    use ResponseTrait;

    protected $service;

    private const ENDPOINT = 'api/v1/dashboard/summary';

    public function __construct()
    {
        $this->service = new Service();

        // Instantiated directly (new ApiDashboardController()) from page controllers,
        // so initController() never runs; bind request/response here.
        $this->request  = service('request');
        $this->response = service('response');
    }

    /**
     * AJAX: GET admin/api/dashboard/summary
     */
    public function apiSummaryJson(): ResponseInterface
    {
        $summary = $this->apiSummary();

        return $this->response->setStatusCode((int) $summary['status'])->setJSON($summary);
    }

    /**
     * PHP-consumed: {status, message, data, total} where data is always the full summary shape.
     *
     * @return array{status: int, message: string, data: array, total: int}
     */
    public function apiSummary(): array
    {
        try {
            $result = $this->service->ApiRequestWithHeaderToken(base_url(self::ENDPOINT), '', 'GET');
            $mapped = ProxyResponseMapper::map($result);
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard proxy failed: ' . $e->getMessage());
            $mapped = ProxyResponseMapper::failure(500);
        }

        $empty = DashboardApi::emptySummary();

        if ($mapped['status'] < 200 || $mapped['status'] > 299 || ! is_array($mapped['data']) || $mapped['data'] === []) {
            if ($mapped['status'] >= 200 && $mapped['status'] <= 299) {
                $mapped = ProxyResponseMapper::failure(500, 'empty dashboard summary');
            }
            $mapped['data'] = $empty;

            return $mapped;
        }

        // Fill any keys the upstream omitted so the view never hits an undefined index.
        $mapped['data'] = array_replace($empty, $mapped['data']);

        return $mapped;
    }
}
