<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Guards api/v1 content endpoints that are called internally (cURL) by the
 * Api*Controller proxies. A request is allowed when:
 *   (a) the forwarded session cookie belongs to a signed-in admin, or
 *   (b) it carries X-Internal-Proxy with a valid HMAC signature
 *       (hash_hmac('sha256', 'tour-travel-internal-proxy', INTERNAL_PROXY_SECRET)).
 * Otherwise a 401 JSON envelope is returned.
 */
class InternalApiFilter implements FilterInterface
{
    public const PROXY_HEADER    = 'X-Internal-Proxy';
    public const SIGNATURE_HEADER = 'X-Internal-Proxy-Signature';
    public const SIGNATURE_PAYLOAD = 'tour-travel-internal-proxy';

    public function before(RequestInterface $request, $arguments = null)
    {
        if (session()->get(AdminAuthFilter::SESSION_FLAG) === true) {
            return null;
        }

        if ($this->hasValidProxySignature($request)) {
            return null;
        }

        return service('response')
            ->setStatusCode(401)
            ->setJSON([
                'status'  => 401,
                'message' => 'Unauthorized',
                'data'    => [],
                'total'   => 0,
            ]);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }

    private function hasValidProxySignature(RequestInterface $request): bool
    {
        $secret = trim((string) env('INTERNAL_PROXY_SECRET', ''));

        if ($secret === '' || $request->getHeaderLine(self::PROXY_HEADER) === '') {
            return false;
        }

        $given    = $request->getHeaderLine(self::SIGNATURE_HEADER);
        $expected = hash_hmac('sha256', self::SIGNATURE_PAYLOAD, $secret);

        return $given !== '' && hash_equals($expected, $given);
    }
}
