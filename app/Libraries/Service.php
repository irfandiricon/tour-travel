<?php

namespace App\Libraries;

/**
 * Internal HTTP client used by Api*Controller proxies to reach api/v1/*.
 * Every method is null-safe: failures return an object with status 500 (never null).
 */
class Service
{
    private const CONNECT_TIMEOUT = 5;
    private const TIMEOUT         = 15;

    /**
     * JSON request to an internal api/v1 endpoint.
     *
     * @param string       $url
     * @param string|array $json    JSON string (or array, encoded here)
     * @param string       $method
     *
     * @return object
     */
    public function ApiRequestWithHeaderToken($url, $json = '', $method = 'POST')
    {
        $payload = is_string($json) ? $json : json_encode($json);
        $headers = array_merge(
            ['Accept: application/json', 'Content-Type: application/json'],
            $this->internalHeaders()
        );

        return $this->execute($url, $method, $headers, ($payload === '' || $payload === '[]' || $payload === '{}') ? null : $payload);
    }

    /**
     * Multipart request (payload may contain \CURLFile values).
     *
     * @param string       $url
     * @param array|string $payload
     * @param string       $method
     *
     * @return object
     */
    public function ApiRequesUploadWithHeaderToken($url, $payload, $method = 'POST')
    {
        $isMultipart = false;

        if (is_array($payload)) {
            foreach ($payload as $value) {
                if ($value instanceof \CURLFile) {
                    $isMultipart = true;
                    break;
                }
            }
        }

        $headers = array_merge(['Accept: application/json'], $this->internalHeaders());

        if (! $isMultipart) {
            $headers[] = 'Content-Type: application/json';
            $payload   = is_array($payload) ? json_encode($payload) : $payload;
        }

        return $this->execute($url, $method, $headers, $payload);
    }

    /**
     * @param string            $url
     * @param string            $method
     * @param list<string>      $headers
     * @param string|array|null $payload
     */
    private function execute($url, $method, array $headers, $payload): object
    {
        if (! function_exists('curl_init')) {
            return $this->fail(500, 'cURL extension is not available');
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->internalUrl($url),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => strtoupper((string) $method),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
        ]);

        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        // Forward the session cookie, then release the session lock so the
        // internal request does not deadlock on the same session file.
        if (session_status() === PHP_SESSION_ACTIVE) {
            $name = session_name();
            $id   = session_id();

            if ($name && $id) {
                curl_setopt($ch, CURLOPT_COOKIE, "{$name}={$id}");
            }

            session_write_close();
        }

        $response = curl_exec($ch);
        $errno    = curl_errno($ch);
        $error    = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            return $this->fail(500, 'CURL error: ' . $error);
        }

        if ($response === false || trim((string) $response) === '') {
            return $this->fail(500, 'Empty response from API');
        }

        $decoded = json_decode((string) $response);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_object($decoded)) {
            return $this->fail($httpCode >= 400 ? $httpCode : 500, 'Response is not valid JSON');
        }

        return $decoded;
    }

    /**
     * Optional dev workaround for single-threaded servers (e.g. `php spark serve`).
     * When INTERNAL_API_BASE_URL is set (e.g. http://localhost:8081/), requests to
     * this app's own base URL are redirected to that second instance so the web
     * request and the internal api/v1 call do not block each other.
     */
    private function internalUrl($url): string
    {
        $url      = (string) $url;
        $internal = rtrim(trim((string) env('INTERNAL_API_BASE_URL', '')), '/');

        if ($internal === '') {
            return $url;
        }

        $base = rtrim(base_url(), '/');

        return str_starts_with($url, $base) ? $internal . substr($url, strlen($base)) : $url;
    }

    /**
     * Shared-secret headers marking the call as an internal proxy request.
     *
     * @return list<string>
     */
    private function internalHeaders(): array
    {
        $secret = trim((string) env('INTERNAL_PROXY_SECRET', ''));

        if ($secret === '') {
            return [];
        }

        return [
            'X-Internal-Proxy: 1',
            'X-Internal-Proxy-Signature: ' . hash_hmac('sha256', 'tour-travel-internal-proxy', $secret),
        ];
    }

    private function fail(int $status, string $message): object
    {
        return (object) ['status' => $status, 'message' => $message, 'data' => []];
    }
}
