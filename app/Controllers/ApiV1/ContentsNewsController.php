<?php

namespace App\Controllers\ApiV1;

use App\Libraries\ContentFilter;
use App\Libraries\ContentValidator;
use App\Libraries\ImageUploadHandler;
use App\Libraries\Paginator;
use App\Models\NewsModel;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

/**
 * api/v1 news content tier: validation + persistence.
 * Response shape: {status, message, data, total}.
 */
class ContentsNewsController extends ResourceController
{
    protected $format = 'json';

    /** Fields accepted from the client. */
    private const INPUT_FIELDS = [
        'title_en', 'title_id', 'title_ms',
        'body_en', 'body_id', 'body_ms',
        'publish_date', 'status',
    ];

    /** Where uploaded pictures are stored (relative to public/). */
    public const UPLOAD_DIR = 'uploads/news';

    protected NewsModel $newsModel;

    public function __construct()
    {
        $this->newsModel = new NewsModel();
    }

    /**
     * GET contents-news?page=&per_page= : all non-deleted news, publish date descending.
     */
    public function index(): ResponseInterface
    {
        $rows = $this->newsModel->notDeleted()
            ->orderBy('publish_date', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll();

        $result = Paginator::paginate(
            $rows,
            $this->request->getGet('page'),
            $this->request->getGet('per_page')
        );

        return $this->respondWith(200, 'OK', $result['data'], $result['total']);
    }

    /**
     * GET contents-news/active : published news already due, newest first.
     */
    public function getAllActive(): ResponseInterface
    {
        $rows = $this->newsModel->published()->findAll();
        $data = ContentFilter::publishedNewsDescending($rows, date('Y-m-d H:i:s'));

        return $this->respondWith(200, 'OK', $data, count($data));
    }

    /**
     * GET contents-news/{id}
     */
    public function show($id = null): ResponseInterface
    {
        $row = $this->findRow($id);
        if ($row === null) {
            return $this->notFound();
        }

        return $this->respondWith(200, 'OK', $row, 1);
    }

    /**
     * POST contents-news : create, publish_date defaults to now.
     */
    public function create(): ResponseInterface
    {
        $input = $this->input();

        $errors = $this->validateNews($input);
        $file   = $this->uploadedFile();
        if ($file !== null) {
            $errors = array_merge($errors, $this->imageErrors($file));
        }
        if ($errors !== []) {
            return $this->invalid($errors);
        }

        $record = $this->pick($input);
        if (empty($record['publish_date'])) {
            $record['publish_date'] = date('Y-m-d H:i:s');
        }
        $record['status'] = $this->normalizeStatus($record['status'] ?? null) ?? 'draft';

        if ($file !== null) {
            $path = $this->storeImage($file);
            if ($path === null) {
                return $this->invalid(['image' => 'Image could not be stored.']);
            }
            $record['image_path'] = $path;
        }

        $newId = $this->newsModel->insert($record);
        if ($newId === false) {
            $this->removeImage($record['image_path'] ?? null);

            return $this->respondWith(500, 'Failed to create news', [], 0);
        }

        return $this->respondWith(201, 'News created', $this->newsModel->find($newId), 1);
    }

    /**
     * PUT/POST contents-news/{id} : update; merged with stored row for validation.
     */
    public function update($id = null): ResponseInterface
    {
        $row = $this->findRow($id);
        if ($row === null) {
            return $this->notFound();
        }

        $input  = $this->input();
        $merged = array_merge($row, $this->pick($input));

        $errors = $this->validateNews($merged);
        $file   = $this->uploadedFile();
        if ($file !== null) {
            $errors = array_merge($errors, $this->imageErrors($file));
        }
        if ($errors !== []) {
            return $this->invalid($errors);
        }

        $record = $this->pick($input);
        if (array_key_exists('status', $record)) {
            $status = $this->normalizeStatus($record['status']);
            if ($status === null) {
                unset($record['status']);
            } else {
                $record['status'] = $status;
            }
        }
        if (array_key_exists('publish_date', $record) && empty($record['publish_date'])) {
            unset($record['publish_date']);
        }

        // A new picture replaces the old one; the "remove" checkbox clears it (ignored when a new one is sent).
        $newPath = null;
        if ($file !== null) {
            $newPath = $this->storeImage($file);
            if ($newPath === null) {
                return $this->invalid(['image' => 'Image could not be stored.']);
            }
            $record['image_path'] = $newPath;
        }
        $removeImage = $newPath === null && (($input['image_remove'] ?? '') === '1');
        if ($removeImage) {
            $record['image_path'] = null;
        }

        if ($record !== [] && ! $this->newsModel->update((int) $id, $record)) {
            $this->removeImage($newPath);

            return $this->respondWith(500, 'Failed to update news', [], 0);
        }

        if ($newPath !== null || $removeImage) {
            $this->removeImage($row['image_path'] ?? null);
        }

        return $this->respondWith(200, 'News updated', $this->newsModel->find((int) $id), 1);
    }

    /**
     * DELETE contents-news/{id} : soft delete.
     */
    public function delete($id = null): ResponseInterface
    {
        if ($this->findRow($id) === null) {
            return $this->notFound();
        }

        $this->newsModel->softDelete((int) $id);

        return $this->respondWith(200, 'News deleted', [], 0);
    }

    /**
     * PUT contents-news/activate/{id} : publish.
     */
    public function activate($id = null): ResponseInterface
    {
        return $this->setStatus($id, 'published', 'News activated');
    }

    /**
     * PUT contents-news/deactivate/{id} : back to draft.
     */
    public function deactivate($id = null): ResponseInterface
    {
        return $this->setStatus($id, 'draft', 'News deactivated');
    }

    // ------------------------------------------------------------------

    private function setStatus($id, string $status, string $message): ResponseInterface
    {
        if ($this->findRow($id) === null) {
            return $this->notFound();
        }

        $this->newsModel->update((int) $id, ['status' => $status]);

        return $this->respondWith(200, $message, $this->newsModel->find((int) $id), 1);
    }

    private function findRow($id): ?array
    {
        if (! is_numeric($id) || (int) $id < 1) {
            return null;
        }

        return $this->newsModel->findActive((int) $id);
    }

    /**
     * @return array<string,string>
     */
    private function validateNews(array $data): array
    {
        $errors = array_merge(
            ContentValidator::validateRequiredEn($data, ['title', 'body']),
            ContentValidator::validateFieldLengths($data, ContentValidator::LIMITS_NEWS)
        );

        if (! empty($data['publish_date']) && strtotime((string) $data['publish_date']) === false) {
            $errors['publish_date'] = 'publish_date is not a valid date';
        }

        return $errors;
    }

    private function normalizeStatus($status): ?string
    {
        return in_array($status, ['draft', 'published'], true) ? $status : null;
    }

    /**
     * @return array<string,mixed>
     */
    private function input(): array
    {
        $json = null;
        if (str_contains((string) $this->request->getHeaderLine('Content-Type'), 'json')) {
            $json = $this->request->getJSON(true);
        }

        $data = is_array($json) ? $json : (array) $this->request->getPost();
        if ($data === []) {
            $raw = $this->request->getRawInput();
            $data = is_array($raw) ? $raw : [];
        }

        return $data;
    }

    /**
     * @return array<string,mixed>
     */
    private function pick(array $input): array
    {
        return array_intersect_key($input, array_flip(self::INPUT_FIELDS));
    }

    private function uploadedFile(): ?UploadedFile
    {
        $file = $this->request->getFile('image');
        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        return $file;
    }

    /** @return array<string,string> */
    private function imageErrors(UploadedFile $file): array
    {
        $handler = new ImageUploadHandler();

        // Upload failures (incl. exceeding php.ini limits) are rejections too.
        if (! $file->isValid()) {
            if (in_array($file->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                return $handler->errors($file->getClientMimeType(), ImageUploadHandler::MAX_SIZE_BYTES + 1);
            }

            return ['image' => 'Image upload failed: ' . $file->getErrorString()];
        }

        return $handler->errors($file->getMimeType(), (int) $file->getSize());
    }

    private function storeImage(UploadedFile $file): ?string
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

    /** Deletes a stored upload; paths outside the news upload folder are never touched. */
    private function removeImage(?string $path): void
    {
        if ($path === null || $path === '' || ! str_starts_with($path, self::UPLOAD_DIR . '/')) {
            return;
        }
        $full = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
        if (is_file($full)) {
            @unlink($full);
        }
    }

    private function invalid(array $errors): ResponseInterface
    {
        return $this->response->setStatusCode(422)->setJSON([
            'status'  => 422,
            'message' => 'Validation failed',
            'errors'  => $errors,   // field-keyed; the admin proxy forwards this key
            'data'    => $errors,   // kept for existing clients
            'total'   => 0,
        ]);
    }

    private function notFound(): ResponseInterface
    {
        return $this->respondWith(404, 'News not found', [], 0);
    }

    private function respondWith(int $status, string $message, $data, int $total): ResponseInterface
    {
        return $this->response->setStatusCode($status)->setJSON([
            'status'  => $status,
            'message' => $message,
            'data'    => $data,
            'total'   => $total,
        ]);
    }
}
