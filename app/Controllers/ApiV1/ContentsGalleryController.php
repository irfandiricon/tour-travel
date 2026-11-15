<?php

namespace App\Controllers\ApiV1;

use App\Libraries\AdminSearch;
use App\Libraries\ImageUploadHandler;
use App\Models\GalleryModel;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

/**
 * api/v1/contents-gallery: gallery CRUD with image upload and ordering
 * (Req 6.7, 6.8, 6.9).
 */
class ContentsGalleryController extends ResourceController
{
    protected $format = 'json';

    protected GalleryModel $model_gallery;
    protected ImageUploadHandler $imageHandler;

    private const UPLOAD_DIR = 'uploads/gallery';

    /** Columns the admin search looks in. */
    public const SEARCH_COLUMNS = ['caption_en', 'caption_id', 'caption_ms', 'status'];

    public function __construct()
    {
        $this->model_gallery = new GalleryModel();
        $this->imageHandler  = new ImageUploadHandler();
    }

    /** GET contents-gallery?per_page=&page= (paginated, non-deleted; admin list). */
    public function index(): ResponseInterface
    {
        $perPage = max(1, min(100, (int) ($this->request->getGet('per_page') ?: 10)));
        $page    = max(1, (int) ($this->request->getGet('page') ?: 1));

        $search = AdminSearch::term($this->request->getGet('search'));
        $total  = AdminSearch::apply((new GalleryModel())->notDeleted(), $search, self::SEARCH_COLUMNS)->countAllResults();
        $rows   = AdminSearch::apply((new GalleryModel())->notDeleted(), $search, self::SEARCH_COLUMNS)
            ->orderBy('id', 'DESC')
            ->findAll($perPage, ($page - 1) * $perPage);

        return $this->respond([
            'status' => 200, 'message' => 'OK', 'data' => $rows,
            'total' => $total, 'page' => $page, 'per_page' => $perPage,
        ]);
    }

    public function show($id = null): ResponseInterface
    {
        $row = $this->model_gallery->findActive((int) $id);
        if ($row === null) {
            return $this->notFound();
        }

        return $this->respond(['status' => 200, 'message' => 'OK', 'data' => $row, 'total' => 1]);
    }

    /** Active (published) items ordered by sort_order. */
    public function getAllActive(): ResponseInterface
    {
        $data = $this->model_gallery->published()
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        return $this->respond(['status' => 200, 'message' => '', 'data' => $data, 'total' => count($data)]);
    }

    public function create(): ResponseInterface
    {
        $fields = $this->collectFields();
        $file   = $this->request->getFile('image');
        $errors = [];

        if (! $this->hasFile($file)) {
            $errors['image'] = 'Image is required.';
        } else {
            $errors = $this->imageHandler->errors($file->getClientMimeType(), (int) $file->getSize());
        }
        $errors += $this->validateSortOrder($fields);

        if ($errors !== []) {
            return $this->unprocessable($errors);
        }

        $fields['image_path'] = $this->storeImage($file);
        $fields['status']     = $this->request->getPost('status') === 'draft' ? 'draft' : 'published';

        $id = $this->model_gallery->insert($fields);
        if ($id === false) {
            return $this->respond(['status' => 500, 'message' => 'Failed to save gallery item.', 'data' => []], 500);
        }

        return $this->respondCreated([
            'status' => 201,
            'message' => '',
            'data' => ['id' => (int) $id, 'status' => $fields['status'], 'image_path' => $fields['image_path']],
        ]);
    }

    public function update($id = null): ResponseInterface
    {
        $row = $this->model_gallery->findActive((int) $id);
        if ($row === null) {
            return $this->notFound();
        }

        $fields = $this->collectFields($row);
        $file   = $this->request->getFile('image');
        $errors = $this->validateSortOrder($fields);

        if ($this->hasFile($file)) {
            $errors += $this->imageHandler->errors($file->getClientMimeType(), (int) $file->getSize());
        }

        if ($errors !== []) {
            return $this->unprocessable($errors); // record left unchanged
        }

        if ($this->hasFile($file)) {
            $fields['image_path'] = $this->storeImage($file);
        }

        $this->model_gallery->update((int) $id, $fields);

        if (isset($fields['image_path']) && ! empty($row['image_path']) && $fields['image_path'] !== $row['image_path']) {
            $this->removeImage($row['image_path']);
        }

        return $this->respond([
            'status' => 200,
            'message' => '',
            'data' => $this->model_gallery->findActive((int) $id),
        ]);
    }

    public function delete($id = null): ResponseInterface
    {
        if ($this->model_gallery->findActive((int) $id) === null) {
            return $this->notFound();
        }

        $this->model_gallery->softDelete((int) $id);

        return $this->respondDeleted(['status' => 200, 'message' => '', 'data' => ['id' => (int) $id]]);
    }

    /**
     * Gather captions / sort_order from request, defaulting to existing values.
     *
     * @param array<string, mixed> $existing
     * @return array<string, mixed>
     */
    private function collectFields(array $existing = []): array
    {
        $fields = [];
        foreach (['caption_en', 'caption_id', 'caption_ms'] as $key) {
            $value = $this->request->getVar($key);
            if ($value !== null) {
                $fields[$key] = trim((string) $value);
            }
        }

        $sort = $this->request->getVar('sort_order');
        if ($sort !== null && $sort !== '') {
            $fields['sort_order'] = $sort;
        } elseif ($existing === []) {
            $fields['sort_order'] = 0;
        }

        return $fields;
    }

    /** @param array<string, mixed> $fields */
    private function validateSortOrder(array &$fields): array
    {
        if (! array_key_exists('sort_order', $fields)) {
            return [];
        }
        if (filter_var($fields['sort_order'], FILTER_VALIDATE_INT) === false || (int) $fields['sort_order'] < 0) {
            return ['sort_order' => 'Sort order must be a non-negative integer.'];
        }
        $fields['sort_order'] = (int) $fields['sort_order'];

        return [];
    }

    private function hasFile(?UploadedFile $file): bool
    {
        return $file !== null && $file->isValid() && $file->getError() !== UPLOAD_ERR_NO_FILE;
    }

    private function storeImage(UploadedFile $file): string
    {
        $dir = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, self::UPLOAD_DIR);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $name = $file->getRandomName();
        $file->move($dir, $name);

        return self::UPLOAD_DIR . '/' . $name;
    }

    private function removeImage(string $path): void
    {
        $full = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
        if (str_starts_with($path, self::UPLOAD_DIR . '/') && is_file($full)) {
            @unlink($full);
        }
    }

    /** @param array<string, string> $errors */
    private function unprocessable(array $errors): ResponseInterface
    {
        return $this->respond(['status' => 422, 'message' => 'Validation failed.', 'errors' => $errors, 'data' => []], 422);
    }

    private function notFound(): ResponseInterface
    {
        return $this->respond(['status' => 404, 'message' => 'Gallery item not found.', 'data' => []], 404);
    }
}
