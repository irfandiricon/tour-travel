<?php

namespace App\Controllers\ApiV1;

use App\Libraries\ContentValidator;
use App\Libraries\ImageUploadHandler;
use App\Libraries\WhatsAppLinkBuilder;
use App\Models\SiteConfigModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * api/v1 site configuration endpoints (Req 6.1-6.4, 7.2-7.4).
 * Owns validation and persistence; extends Controller (not BaseController).
 */
class SiteConfigController extends Controller
{
    use ResponseTrait;

    protected $siteConfigModel;

    /** Columns an admin may submit. */
    public const FIELDS = [
        'hero_title_en', 'hero_title_id', 'hero_title_ms',
        'hero_subtitle_en', 'hero_subtitle_id', 'hero_subtitle_ms',
        'about_body_en', 'about_body_id', 'about_body_ms',
        'gallery_title_en', 'gallery_title_id', 'gallery_title_ms',
        'contact_body_en', 'contact_body_id', 'contact_body_ms',
        'contact_email', 'contact_address', 'whatsapp_number',
        'site_name_en', 'site_name_id', 'site_name_ms',
        'meta_description_en', 'meta_description_id', 'meta_description_ms',
        'meta_keywords_en', 'meta_keywords_id', 'meta_keywords_ms',
        'og_image',
        'about_title_en', 'about_title_id', 'about_title_ms', 'about_image',
    ];

    /** Image upload fields and the public/ folder each one is stored in. */
    public const IMAGE_DIRS = ['og_image' => 'uploads/seo', 'about_image' => 'uploads/about'];

    public function __construct()
    {
        $this->siteConfigModel = new SiteConfigModel();
    }

    /** GET site-config (single active row). */
    public function getConfig()
    {
        $row = $this->siteConfigModel->getConfig();

        return $this->response->setJSON([
            'status'  => 200,
            'message' => 'OK',
            'data'    => $row ?? [],
            'total'   => $row ? 1 : 0,
        ]);
    }

    /** POST/PUT site-config/save */
    public function save()
    {
        $existing = $this->siteConfigModel->getConfig();
        $result   = self::prepareSave($existing, $this->payload());

        // Image uploads (og_image, about_image) are validated before any write (Req 11.20).
        $files = [];
        foreach (array_keys(self::IMAGE_DIRS) as $field) {
            $file = $this->uploadedFile($field);
            if ($file !== null) {
                $files[$field]    = $file;
                $result['errors'] = array_merge($result['errors'], $this->imageErrors($file, $field));
            }
        }

        $newPaths = [];
        if (! $result['errors']) {
            foreach ($files as $field => $file) {
                $stored = $this->storeImage($file, $field);
                if ($stored === null) {
                    $result['errors'][$field] = 'Image could not be stored.';
                    break;
                }
                $newPaths[$field]         = $stored;
                $result['record'][$field] = $stored;
            }
        }

        // "Remove current image" checkbox for the About picture (ignored when a new one is uploaded).
        $removeAbout = ! isset($newPaths['about_image']) && ($this->payload()['about_image_remove'] ?? '') === '1';
        if (! $result['errors'] && $removeAbout) {
            $result['record']['about_image'] = null;
        }

        if ($result['errors']) {
            foreach ($newPaths as $path) {
                $this->removeImage($path);
            }
            // Nothing is persisted: previous configuration stays unchanged (Req 6.2).
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 422,
                'message' => 'Validation failed: ' . implode('; ', $result['errors']),
                'errors'  => $result['errors'],
                'data'    => [],
                'total'   => 0,
            ]);
        }
        if ($existing === null) {
            $id = $this->siteConfigModel->insert($result['record'], true);
        } else {
            $id = (int) $existing['id'];
            $this->siteConfigModel->update($id, $result['record']);
        }

        if (! $id) {
            foreach ($newPaths as $path) {
                $this->removeImage($path);
            }

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 500, 'message' => 'Failed to save configuration', 'data' => [], 'total' => 0,
            ]);
        }

        // The replaced / removed pictures are no longer referenced: delete their files.
        foreach (array_keys($newPaths) as $field) {
            $this->removeImage($existing[$field] ?? null);
        }
        if ($removeAbout) {
            $this->removeImage($existing['about_image'] ?? null);
        }

        $saved = $this->siteConfigModel->find((int) $id);

        return $this->response->setJSON([
            'status'  => 200,
            'message' => 'Configuration saved',
            'data'    => $saved ?? $result['record'],
            'total'   => 1,
        ]);
    }

    /**
     * Pure save logic (no DB, no request): merge the submitted data over the existing
     * config, validate, and return the record to persist.
     *
     * - hero/about/gallery/contact must have en, id and ms content (Req 6.4)
     * - whatsapp_number must be valid E.164 when non-empty; empty is allowed and
     *   disables booking (Req 7.2-7.4)
     *
     * @param array<string,mixed>|null $existing Stored config row, or null if none yet
     * @param array<string,mixed>      $input    Submitted fields (partial allowed)
     * @return array{errors: array<string,string>, record: array<string,mixed>}
     *         On errors, `record` is empty: nothing should be persisted.
     */
    public static function prepareSave(?array $existing, array $input): array
    {
        $submitted = [];
        foreach (self::FIELDS as $field) {
            if (array_key_exists($field, $input)) {
                $value = $input[$field];
                $submitted[$field] = is_scalar($value) ? trim((string) $value) : null;
            }
        }

        $merged = array_merge($existing ?? [], $submitted);

        $errors = array_merge(
            ContentValidator::validateSectionLanguages($merged),
            ContentValidator::validateSeoSettings($merged),
            ContentValidator::validateAboutSettings($merged)
        );

        $number = $merged['whatsapp_number'] ?? null;
        $number = is_scalar($number) ? trim((string) $number) : '';
        if ($number !== '' && ! WhatsAppLinkBuilder::isValidNumber($number)) {
            $errors['whatsapp_number'] = 'whatsapp_number must be in international format, e.g. +628123456789';
        }

        if ($errors) {
            return ['errors' => $errors, 'record' => []];
        }

        // Empty number and optional text fields are stored as NULL.
        $optionals = ['whatsapp_number', 'contact_email', 'contact_address', 'og_image', 'about_image', 'about_title_en', 'about_title_id', 'about_title_ms'];
        foreach (ContentValidator::LIMITS_SEO as $base => $unused) {
            foreach (ContentValidator::LANGUAGES as $lang) {
                $optionals[] = $base . '_' . $lang;
            }
        }
        foreach ($optionals as $optional) {
            if (array_key_exists($optional, $submitted) && $submitted[$optional] === '') {
                $submitted[$optional] = null;
            }
        }

        return ['errors' => [], 'record' => $submitted];
    }

    /** Kept for backwards compatibility: the folder of the social sharing image. */
    public const UPLOAD_DIR = 'uploads/seo';

    private function uploadedFile(string $field): ?UploadedFile
    {
        $file = $this->request->getFile($field);
        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        return $file;
    }

    /** @return array<string,string> */
    private function imageErrors(UploadedFile $file, string $field): array
    {
        $handler = new ImageUploadHandler();
        if (! $file->isValid()) {
            if (in_array($file->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                return $handler->errors($file->getClientMimeType(), ImageUploadHandler::MAX_SIZE_BYTES + 1, $field);
            }

            return [$field => 'Image upload failed: ' . $file->getErrorString()];
        }

        return $handler->errors($file->getMimeType(), (int) $file->getSize(), $field);
    }

    private function storeImage(UploadedFile $file, string $field): ?string
    {
        $relative = self::IMAGE_DIRS[$field] ?? self::UPLOAD_DIR;
        $dir      = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            return null;
        }

        $ext  = match ($file->getMimeType()) {
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'jpg',
        };
        $name = bin2hex(random_bytes(16)) . '.' . $ext;

        try {
            $file->move($dir, $name);
        } catch (\Throwable $e) {
            return null;
        }

        return $relative . '/' . $name;
    }

    /** Deletes a stored upload; paths outside the upload folders (e.g. hand-placed images) are never touched. */
    private function removeImage(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }
        foreach (self::IMAGE_DIRS as $relative) {
            if (str_starts_with($path, $relative . '/')) {
                $full = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
                if (is_file($full)) {
                    @unlink($full);
                }

                return;
            }
        }
    }
    private function payload(): array
    {
        // Prefer form/multipart fields; only parse the body as JSON when there are none.
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