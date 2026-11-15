<?php

namespace App\Controllers\ApiV1;

use App\Libraries\AdminSearch;
use App\Libraries\ImageUploadHandler;
use App\Libraries\TestimonialValidator;
use App\Models\TestimonialModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * api/v1/contents-testimonials: customer reviews with a 1-5 star rating and an optional profile photo.
 * Extends Controller (not BaseController). The photo is sent as multipart field `image`
 * (POST .../update/{id} on edit, because PHP does not parse multipart on PUT).
 */
class ContentsTestimonialsController extends Controller
{
    use ResponseTrait;

    /** Where profile photos are stored (relative to public/). */
    public const UPLOAD_DIR = 'uploads/testimonials';

    /** Columns the admin search looks in. */
    public const SEARCH_COLUMNS = ['customer_name', 'review_en', 'review_id', 'review_ms', 'status'];

    protected $testimonialModel;
    protected $imageHandler;

    public function __construct()
    {
        $this->testimonialModel = new TestimonialModel();
        $this->imageHandler     = new ImageUploadHandler();
    }

    /** GET contents-testimonials?per_page=&page= (admin list, newest first, non-deleted). */
    public function index(): ResponseInterface
    {
        $perPage = max(1, min(100, (int) ($this->request->getGet('per_page') ?: 10)));
        $page    = max(1, (int) ($this->request->getGet('page') ?: 1));

        $search = AdminSearch::term($this->request->getGet('search'));
        $total  = AdminSearch::apply((new TestimonialModel())->notDeleted(), $search, self::SEARCH_COLUMNS)->countAllResults();
        $rows   = AdminSearch::apply((new TestimonialModel())->notDeleted(), $search, self::SEARCH_COLUMNS)->orderBy('id', 'DESC')->findAll($perPage, ($page - 1) * $perPage);

        foreach ($rows as &$row) {
            $row['stars'] = TestimonialValidator::stars($row['rating'] ?? 0);
        }
        unset($row);

        return $this->response->setJSON([
            'status' => 200, 'message' => 'OK', 'data' => $rows,
            'total' => $total, 'page' => $page, 'per_page' => $perPage,
        ]);
    }

    /** GET contents-testimonials/active (published, newest first; landing page). */
    public function getAllActive(): ResponseInterface
    {
        $rows = (new TestimonialModel())->published()->orderBy('id', 'DESC')->findAll();

        return $this->response->setJSON(['status' => 200, 'message' => 'OK', 'data' => $rows, 'total' => count($rows)]);
    }

    /** GET contents-testimonials/(:num) */
    public function show($id = null): ResponseInterface
    {
        $row = $this->testimonialModel->findActive((int) $id);
        if ($row === null) {
            return $this->notFound();
        }

        return $this->response->setJSON(['status' => 200, 'message' => 'OK', 'data' => $row, 'total' => 1]);
    }

    /** POST contents-testimonials */
    public function create(): ResponseInterface
    {
        $input  = $this->input();
        $errors = TestimonialValidator::validate($input);
        [$file, $fileErrors] = $this->photo();
        $errors += $fileErrors;
        if ($errors !== []) {
            return $this->invalid($errors);
        }

        $record           = $this->pick($input);
        $record['status'] = ($input['status'] ?? '') === 'draft' ? 'draft' : 'published';

        if ($file !== null) {
            $record['photo_path'] = $this->storePhoto($file);
            if ($record['photo_path'] === null) {
                return $this->invalid(['image' => 'The photo could not be stored.']);
            }
        }

        $id = $this->testimonialModel->insert($record, true);
        if (! $id) {
            $this->removePhoto($record['photo_path'] ?? null);

            return $this->response->setStatusCode(500)->setJSON(['status' => 500, 'message' => 'Failed to save review', 'data' => [], 'total' => 0]);
        }

        return $this->response->setStatusCode(201)->setJSON([
            'status' => 201, 'message' => 'Review created', 'data' => ['id' => (int) $id], 'total' => 1,
        ]);
    }

    /** PUT/POST contents-testimonials/(:num). `image_remove=1` removes the current photo. */
    public function update($id = null): ResponseInterface
    {
        $id  = (int) $id;
        $row = $this->testimonialModel->findActive($id);
        if ($row === null) {
            return $this->notFound();
        }

        $input  = $this->input();
        $record = $this->pick($input);
        $errors = TestimonialValidator::validate(array_merge($row, $record));
        [$file, $fileErrors] = $this->photo();
        $errors += $fileErrors;
        if ($errors !== []) {
            return $this->invalid($errors); // record left unchanged
        }

        if (in_array($input['status'] ?? null, ['draft', 'published'], true)) {
            $record['status'] = $input['status'];
        }

        $newPath = null;
        if ($file !== null) {
            $newPath = $this->storePhoto($file);
            if ($newPath === null) {
                return $this->invalid(['image' => 'The photo could not be stored.']);
            }
            $record['photo_path'] = $newPath;
        }
        $remove = $newPath === null && (string) ($input['image_remove'] ?? '') === '1';
        if ($remove) {
            $record['photo_path'] = null;
        }

        if ($record !== [] && ! $this->testimonialModel->update($id, $record)) {
            $this->removePhoto($newPath);

            return $this->response->setStatusCode(500)->setJSON(['status' => 500, 'message' => 'Failed to update review', 'data' => [], 'total' => 0]);
        }
        if ($newPath !== null || $remove) {
            $this->removePhoto($row['photo_path'] ?? null);
        }

        return $this->response->setJSON(['status' => 200, 'message' => 'Review updated', 'data' => ['id' => $id], 'total' => 1]);
    }

    /** DELETE contents-testimonials/(:num) (soft delete, like the other content). */
    public function delete($id = null): ResponseInterface
    {
        if ($this->testimonialModel->findActive((int) $id) === null) {
            return $this->notFound();
        }
        $this->testimonialModel->softDelete((int) $id);

        return $this->response->setJSON(['status' => 200, 'message' => 'Review deleted', 'data' => ['id' => (int) $id], 'total' => 1]);
    }

    // ------------------------------------------------------------- helpers

    /** @return array<string,mixed> */
    private function input(): array
    {
        if (str_contains(strtolower($this->request->getHeaderLine('Content-Type')), 'json')) {
            $json = $this->request->getJSON(true);

            return is_array($json) ? $json : [];
        }

        return $this->request->getPost() ?: [];
    }

    /** Only the fields present in the request, trimmed; rating as an int. */
    private function pick(array $input): array
    {
        $out = [];
        if (array_key_exists('customer_name', $input) && is_scalar($input['customer_name'])) {
            $out['customer_name'] = trim((string) $input['customer_name']);
        }
        if (array_key_exists('rating', $input)) {
            $out['rating'] = TestimonialValidator::rating(is_scalar($input['rating']) ? (string) $input['rating'] : null) ?? $input['rating'];
        }
        foreach (TestimonialValidator::REVIEW_LANGS as $lang) {
            $field = 'review_' . $lang;
            if (array_key_exists($field, $input) && is_scalar($input[$field])) {
                $value       = trim(str_replace("\r\n", "\n", (string) $input[$field]));
                $out[$field] = $value === '' && $lang !== 'en' ? null : $value;
            }
        }

        return $out;
    }

    /**
     * The uploaded photo (or null when none was sent) and its errors. The type is checked on the
     * file content, not the name or the type the browser claims.
     *
     * @return array{0: UploadedFile|null, 1: array<string,string>}
     */
    private function photo(): array
    {
        $file = $this->request->getFile('image');
        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return [null, []];
        }
        if (! $file->isValid()) {
            return [null, ['image' => 'The photo could not be uploaded. Use an image of 5 MB or less.']];
        }
        $errors = $this->imageHandler->errors($file->getMimeType(), (int) $file->getSize());

        return [$errors === [] ? $file : null, $errors];
    }

    private function storePhoto(UploadedFile $file): ?string
    {
        $dir = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, self::UPLOAD_DIR);
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

        return self::UPLOAD_DIR . '/' . $name;
    }

    /** Deletes a stored photo; paths outside the testimonial folder are never touched. */
    private function removePhoto(?string $path): void
    {
        if ($path === null || $path === '' || ! str_starts_with($path, self::UPLOAD_DIR . '/') || str_contains($path, '..')) {
            return;
        }
        $full = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
        if (is_file($full)) {
            @unlink($full);
        }
    }

    private function notFound(): ResponseInterface
    {
        return $this->response->setStatusCode(404)->setJSON(['status' => 404, 'message' => 'Review not found', 'data' => [], 'total' => 0]);
    }

    /** @param array<string,string> $errors */
    private function invalid(array $errors): ResponseInterface
    {
        return $this->response->setStatusCode(422)->setJSON([
            'status'  => 422,
            'message' => 'Validation failed: ' . implode('; ', $errors),
            'errors'  => $errors,
            'data'    => $errors,
            'total'   => 0,
        ]);
    }
}