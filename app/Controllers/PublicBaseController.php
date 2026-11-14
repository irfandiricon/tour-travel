<?php

namespace App\Controllers;

use App\Libraries\LanguageResolver;
use App\Libraries\Service;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Base for unauthenticated public pages. Wires $this->service and language
 * resolution; never requires login.
 */
abstract class PublicBaseController extends Controller
{
    protected $service;

    /** Active language (en|id|ms). */
    protected string $lang = LanguageResolver::DEFAULT;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->service = new Service();
        $this->lang    = $this->resolveLanguage();
    }

    /** Query ?lang -> session('lang') -> 'en'. */
    protected function resolveLanguage(): string
    {
        $query = $this->request->getGet('lang');
        if (is_string($query) && $query !== '') {
            return LanguageResolver::normalize($query);
        }

        $stored = session('lang');

        return LanguageResolver::normalize(is_string($stored) ? $stored : null);
    }
}
