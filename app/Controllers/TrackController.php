<?php

namespace App\Controllers;

use App\Libraries\ContentValidator;
use App\Libraries\DeviceDetector;
use App\Libraries\VisitorHasher;
use App\Models\SiteEventModel;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * Anonymous booking click beacon (Req 12.9-12.13). Lives outside the admin and
 * CSRF filters; protected by same-origin check, payload validation and a
 * per-visitor rate limit. Never affects navigation: always answers quickly.
 */
class TrackController extends PublicBaseController
{
    private const RATE_LIMIT = 10;
    private const RATE_TTL   = 60;

    public function booking(): ResponseInterface
    {
        $userAgent = $this->request->getHeaderLine('User-Agent');

        if (DeviceDetector::isBot($userAgent)) {
            return $this->response->setStatusCode(204);
        }

        if (! $this->isSameOrigin()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 403, 'message' => 'Forbidden']);
        }

        $data = $this->payload();
        $errors = ContentValidator::validateBookingClick($data);
        if ($errors !== []) {
            return $this->response->setStatusCode(422)->setJSON(['status' => 422, 'errors' => $errors]);
        }

        try {
            $now  = date('Y-m-d H:i:s');
            $hash = VisitorHasher::hash(
                (string) $this->request->getIPAddress(),
                $userAgent,
                substr($now, 0, 10),
                hash('sha256', 'visitor-salt|' . (string) (config('Encryption')->key ?? ''))
            );

            if (! $this->allow($hash)) {
                return $this->response->setStatusCode(429)
                    ->setHeader('Retry-After', (string) self::RATE_TTL)
                    ->setJSON(['status' => 429, 'message' => 'Too many requests']);
            }

            (new SiteEventModel())->insert([
                'event_type'   => 'booking_click',
                'ref_id'       => (int) trim((string) $data['fleet_id']),
                'ref_label'    => mb_substr(trim((string) $data['fleet_name']), 0, 200),
                'lang'         => substr((string) $data['lang'], 0, 2),
                'visitor_hash' => $hash,
                'created_at'   => $now,
            ]);
        } catch (Throwable $e) {
            log_message('error', 'Booking click tracking failed: {msg}', ['msg' => $e->getMessage()]);

            return $this->response->setStatusCode(500)->setJSON(['status' => 500, 'message' => 'Unable to record event']);
        }

        return $this->response->setStatusCode(200)->setJSON(['status' => 200]);
    }

    /** Form fields or JSON body. */
    private function payload(): array
    {
        $json = null;
        if (str_contains(strtolower($this->request->getHeaderLine('Content-Type')), 'json')) {
            $json = json_decode((string) $this->request->getBody(), true);
        }

        if (is_array($json)) {
            return $json;
        }

        return [
            'fleet_id'   => $this->request->getPost('fleet_id'),
            'fleet_name' => $this->request->getPost('fleet_name'),
            'lang'       => $this->request->getPost('lang'),
        ];
    }

    /** Origin/Referer, when present, must match the site host. */
    private function isSameOrigin(): bool
    {
        $siteHosts = array_filter([
            strtolower((string) parse_url(base_url(), PHP_URL_HOST)),
            strtolower((string) parse_url((string) $this->request->getUri(), PHP_URL_HOST)),
        ]);

        foreach (['Origin', 'Referer'] as $header) {
            $value = trim($this->request->getHeaderLine($header));
            if ($value === '' || $value === 'null' && $header === 'Origin') {
                continue;
            }
            $host = strtolower((string) parse_url($value, PHP_URL_HOST));

            return $host !== '' && in_array($host, $siteHosts, true);
        }

        return true;
    }

    /** Fixed-window counter in the CI cache; true while under the limit. */
    private function allow(string $hash): bool
    {
        $cache = cache();
        $key   = 'track_booking_' . $hash;
        $count = (int) $cache->get($key);

        if ($count >= self::RATE_LIMIT) {
            return false;
        }

        // Keep the original window: only set TTL on the first hit.
        $cache->save($key, $count + 1, $count === 0 ? self::RATE_TTL : max(1, (int) ($cache->getMetaData($key)['expire'] ?? time() + self::RATE_TTL) - time()));

        return true;
    }
}
