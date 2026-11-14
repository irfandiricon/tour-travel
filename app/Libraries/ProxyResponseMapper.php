<?php

namespace App\Libraries;

/**
 * Pure, null-safe mapping of a Service (cURL) result into the proxy envelope
 * {status, message, data, total}. Shared by every Api*Controller proxy.
 *
 * Contract: any failure (null / empty / non-JSON / missing or non-2xx status)
 * maps to a non-2xx status with an "upstream request failed" message, an empty
 * data set and total 0. It never reports a false success and never throws.
 */
class ProxyResponseMapper
{
    public const FAILURE_MESSAGE = 'Upstream request failed';

    /**
     * @param mixed $result object|array|null returned by Service::ApiRequest*()
     *
     * @return array{status: int, message: string, data: mixed, total: int}
     */
    public static function map($result): array
    {
        $result = self::normalize($result);

        if ($result === null) {
            return self::failure(500);
        }

        $status = $result['status'] ?? null;

        if (! is_int($status) && ! (is_string($status) && ctype_digit($status))) {
            return self::failure(500, self::extractMessage($result), self::extractErrors($result));
        }

        $status = (int) $status;

        if ($status < 200 || $status > 299) {
            // Keep the upstream error status when it is an error code, never a success one.
            return self::failure($status >= 400 && $status <= 599 ? $status : 500, self::extractMessage($result), self::extractErrors($result));
        }

        $data = $result['data'] ?? [];

        return [
            'status'  => $status,
            'message' => self::extractMessage($result),
            'data'    => $data,
            'total'   => isset($result['total']) && is_numeric($result['total'])
                ? (int) $result['total']
                : 0,
        ];
    }

    /**
     * Map a PHP upload error code to a 422 envelope with a field error on `$field` (default `image`).
     * Returns null when there is nothing wrong (UPLOAD_ERR_OK or no file sent).
     * Without this, PHP silently drops an oversized file and the save looks successful.
     *
     * @return array{status: int, message: string, data: array, total: int, errors: array<string,string>}|null
     */
    public static function uploadFailure(int $errorCode, string $field = 'image'): ?array
    {
        if ($errorCode === UPLOAD_ERR_OK || $errorCode === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $message = match ($errorCode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The image is larger than the server upload limit. Use an image of 5 MB or less.',
            UPLOAD_ERR_PARTIAL                        => 'The image was only partially uploaded. Please try again.',
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION => 'The server could not store the uploaded image.',
            default                                   => 'The image could not be uploaded.',
        };

        return [
            'status'  => 422,
            'message' => $message,
            'data'    => [],
            'total'   => 0,
            'errors'  => [$field => $message],
        ];
    }
    /**
     * Build the failure envelope (always non-2xx, always flagged as upstream failure).
     *
     * An optional per-field "errors" map (e.g. 422 validation) is preserved.
     *
     * @return array{status: int, message: string, data: array, total: int, errors?: array}
     */
    public static function failure(int $status = 500, string $detail = '', array $errors = []): array
    {
        if ($status < 400 || $status > 599) {
            $status = 500;
        }

        $detail = trim($detail);

        $envelope = [
            'status'  => $status,
            'message' => $detail === '' ? self::FAILURE_MESSAGE : self::FAILURE_MESSAGE . ': ' . $detail,
            'data'    => [],
            'total'   => 0,
        ];

        if ($errors !== []) {
            $envelope['errors'] = $errors;
        }

        return $envelope;
    }

    /**
     * Convert object/array results to an array; anything else is unusable (null).
     */
    private static function normalize($result): ?array
    {
        if (is_object($result)) {
            $result = json_decode(json_encode($result), true);
        }

        return is_array($result) && $result !== [] ? $result : null;
    }

    /**
     * Per-field validation errors (field => message|list) from the upstream result.
     */
    private static function extractErrors(array $result): array
    {
        $errors = $result['errors'] ?? null;

        return is_array($errors) ? $errors : [];
    }

    /**
     * Read the message from CI failure format (messages.error) or plain message.
     */
    private static function extractMessage(array $result): string
    {
        $message = $result['message'] ?? null;

        if ($message === null && isset($result['messages'])) {
            $messages = $result['messages'];
            $message  = is_array($messages) ? ($messages['error'] ?? $messages) : $messages;
        }

        if (is_array($message)) {
            $message = implode('; ', array_map(
                static fn ($m) => is_scalar($m) ? (string) $m : json_encode($m),
                $message
            ));
        }

        return is_scalar($message) ? (string) $message : '';
    }
}
