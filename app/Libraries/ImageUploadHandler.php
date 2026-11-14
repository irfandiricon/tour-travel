<?php

namespace App\Libraries;

/**
 * Pure validator for image uploads (Req 6.7, 6.8, 6.9).
 * Operates on mime type and byte size only, so it needs no real files.
 */
class ImageUploadHandler
{
    public const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
    public const MAX_SIZE_BYTES = 5 * 1024 * 1024;

    public const ERROR_TYPE = 'invalid_type';
    public const ERROR_SIZE = 'file_too_large';

    /**
     * Validate an upload.
     *
     * @return array{valid: bool, violations: list<array{code: string, message: string}>}
     */
    public function validate(?string $type, int $size): array
    {
        $violations = [];

        if ($type === null || ! in_array($type, self::ALLOWED_TYPES, true)) {
            $violations[] = [
                'code'    => self::ERROR_TYPE,
                'message' => 'Allowed image types are JPEG, PNG, and WebP.',
            ];
        }

        if ($size > self::MAX_SIZE_BYTES) {
            $violations[] = [
                'code'    => self::ERROR_SIZE,
                'message' => 'Image size must not exceed the 5 MB maximum.',
            ];
        }

        return ['valid' => $violations === [], 'violations' => $violations];
    }

    public function isAccepted(?string $type, int $size): bool
    {
        return $this->validate($type, $size)['valid'];
    }

    /**
     * Field-keyed error map (suitable for 422 responses); empty when accepted.
     *
     * @return array<string, string>
     */
    public function errors(?string $type, int $size, string $field = 'image'): array
    {
        $result = $this->validate($type, $size);

        return $result['valid'] ? [] : [$field => implode(' ', array_column($result['violations'], 'message'))];
    }
}
