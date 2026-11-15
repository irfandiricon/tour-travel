<?php

namespace App\Controllers\ApiV1;

use App\Libraries\AdminSearch;
use App\Libraries\ContentFilter;
use App\Libraries\ContentValidator;
use App\Libraries\ImageUploadHandler;
use App\Libraries\Paginator;
use App\Models\FleetModel;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\RESTful\ResourceController;

/**
 * api/v1 fleet endpoints (Req 6.5, 6.7, 6.8, 6.9, 4.8).
 * Image uploads are validated before any DB write, so a rejected upload
 * leaves the stored record untouched.
 */
class ContentsFleetController extends ResourceController
{
    protected $format = 'json';

    /** Public uploads sub-path (relative to FCPATH). */
    public const UPLOAD_DIR = 'uploads/fleet';

    /** @var FleetModel (untyped: must match ResourceController::$model) */
    protected $model;
    protected ImageUploadHandler $imageHandler;

    private const TEXT_FIELDS = [
        'name_en', 'name_id', 'name_ms',
        'description_en', 'description_id', 'description_ms',
    ];

    public function __construct()
    {
        $this->model        = new FleetModel();
        $this->imageHandler = new ImageUploadHandler();
    }

    /** Columns the admin search looks in. */
    public const SEARCH_COLUMNS = ['name_en', 'name_id', 'name_ms', 'description_en', 'description_id', 'description_ms', 'status'];

    /** GET contents-fleet?page=&per_page=&search= (paginated, non-deleted). */
    public function index()
    {
        AdminSearch::apply($this->model->notDeleted(), AdminSearch::term($this->request->getGet('search')), self::SEARCH_COLUMNS);
        $rows = $this->model->orderBy('id', 'DESC')->findAll();
        $page = Paginator::paginate(
            $rows,
            $this->request->getGet('page'),
            $this->request->getGet('per_page')
        );

        return $this->respond([
            'status'   => 200,
            'message'  => 'OK',
            'data'     => $page['data'],
            'total'    => $page['total'],
            'page'     => $page['page'],
            'per_page' => $page['per_page'],
        ]);
    }

    /** GET contents-fleet/active (published only). */
    public function getAllActive()
    {
        $rows = ContentFilter::publishedOnly($this->model->notDeleted()->orderBy('id', 'DESC')->findAll());

        return $this->respond([
            'status'  => 200,
            'message' => 'OK',
            'data'    => $rows,
            'total'   => count($rows),
        ]);
    }

    /** GET contents-fleet/(:num) */
    public function show($id = null)
    {
        $row = $this->model->findActive((int) $id);
        if ($row === null) {
            return $this->notFoundResponse();
        }

        return $this->respond(['status' => 200, 'message' => 'OK', 'data' => $row]);
    }

    /** POST contents-fleet (multipart or JSON). */
    public function create()
    {
        $input  = $this->input();
        $errors = ContentValidator::validateRequiredEn($input, ['name']);
        $errors = array_merge($errors, $this->lengthErrors($input));

        $file = $this->uploadedFile();
        if ($file !== null) {
            $errors = array_merge($errors, $this->imageErrors($file));
        }
        if ($errors !== []) {
            return $this->invalid($errors);
        }

        $data = $this->pick($input);
        $data['status'] = $this->status($input['status'] ?? null, 'draft');
        if ($file !== null) {
            $path = $this->storeImage($file);
            if ($path === null) {
                return $this->invalid(['image' => 'Image could not be stored.'], 500);
            }
            $data['image_path'] = $path;
        }

        $id = $this->model->insert($data, true);
        if (! $id) {
            $this->removeImage($data['image_path'] ?? null);

            return $this->respond(['status' => 500, 'message' => 'Failed to save fleet item'], 500);
        }

        return $this->respondCreated([
            'status'  => 201,
            'message' => 'Created',
            'data'    => $this->model->find($id),
        ]);
    }

    /** PUT/PATCH/POST contents-fleet/(:num) */
    public function update($id = null)
    {
        $existing = $this->model->findActive((int) $id);
        if ($existing === null) {
            return $this->notFoundResponse();
        }

        $input = $this->input();
        // Partial update: validate only fields that were submitted, merged over stored values.
        $merged = array_merge($existing, $input);
        $errors = ContentValidator::validateRequiredEn($merged, ['name']);
        $errors = array_merge($errors, $this->lengthErrors($input));

        $file = $this->uploadedFile();
        if ($file !== null) {
            $errors = array_merge($errors, $this->imageErrors($file));
        }
        if ($errors !== []) {
            return $this->invalid($errors);
        }

        $data = $this->pick($input);
        if (isset($input['status'])) {
            $data['status'] = $this->status($input['status'], (string) $existing['status']);
        }

        $newPath = null;
        if ($file !== null) {
            $newPath = $this->storeImage($file);
            if ($newPath === null) {
                return $this->invalid(['image' => 'Image could not be stored.'], 500);
            }
            $data['image_path'] = $newPath;
        }

        if ($data !== [] && ! $this->model->update((int) $id, $data)) {
            $this->removeImage($newPath);

            return $this->respond(['status' => 500, 'message' => 'Failed to update fleet item'], 500);
        }
        if ($newPath !== null) {
            $this->removeImage($existing['image_path'] ?? null);
        }

        return $this->respond(['status' => 200, 'message' => 'Updated', 'data' => $this->model->find((int) $id)]);
    }

    /** DELETE contents-fleet/(:num) (soft delete). */
    public function delete($id = null)
    {
        if ($this->model->findActive((int) $id) === null) {
            return $this->notFoundResponse();
        }
        $this->model->softDelete((int) $id);

        return $this->respond(['status' => 200, 'message' => 'Deleted']);
    }

    /** POST contents-fleet/activate/(:num) -> published */
    public function activate($id = null)
    {
        return $this->setStatus($id, 'published');
    }

    /** POST contents-fleet/deactivate/(:num) -> draft */
    public function deactivate($id = null)
    {
        return $this->setStatus($id, 'draft');
    }

    private function setStatus($id, string $status)
    {
        if ($this->model->findActive((int) $id) === null) {
            return $this->notFoundResponse();
        }
        $this->model->update((int) $id, ['status' => $status]);

        return $this->respond(['status' => 200, 'message' => 'OK', 'data' => $this->model->find((int) $id)]);
    }

    /** @return array<string,mixed> */
    private function input(): array
    {
        $post = $this->request->getPost();
        if (is_array($post) && $post !== []) {
            return $post;
        }
        // Only parse a JSON body when the request says it is JSON. A multipart request that
        // carries just a file has an empty $_POST and a non-JSON body.
        if (! str_contains(strtolower($this->request->getHeaderLine('Content-Type')), 'json')) {
            return [];
        }

        $json = $this->request->getJSON(true);

        return is_array($json) ? $json : [];
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
        // Upload failures (incl. exceeding php.ini limits) are rejections too.
        if (! $file->isValid()) {
            $size = $file->getError() === UPLOAD_ERR_INI_SIZE || $file->getError() === UPLOAD_ERR_FORM_SIZE
                ? ImageUploadHandler::MAX_SIZE_BYTES + 1
                : 0;
            if ($size > 0) {
                return $this->imageHandler->errors($file->getClientMimeType(), $size);
            }

            return ['image' => 'Image upload failed: ' . $file->getErrorString()];
        }

        return $this->imageHandler->errors($file->getMimeType(), (int) $file->getSize());
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

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    private function pick(array $input): array
    {
        $out = [];
        foreach (self::TEXT_FIELDS as $f) {
            if (array_key_exists($f, $input)) {
                $out[$f] = is_scalar($input[$f]) ? trim((string) $input[$f]) : '';
            }
        }

        return $out;
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,string>
     */
    private function lengthErrors(array $input): array
    {
        $errors = [];
        foreach (['name' => 200, 'description' => 5000] as $base => $max) {
            foreach (ContentValidator::LANGUAGES as $lang) {
                $key = "{$base}_{$lang}";
                if (isset($input[$key]) && is_scalar($input[$key]) && mb_strlen((string) $input[$key], 'UTF-8') > $max) {
                    $errors[$key] = "{$key} must not exceed {$max} characters";
                }
            }
        }

        return $errors;
    }

    /** @param mixed $value */
    private function status($value, string $default): string
    {
        return in_array($value, ['draft', 'published'], true) ? $value : $default;
    }

    private function notFoundResponse()
    {
        return $this->respond(['status' => 404, 'message' => 'Fleet item not found'], 404);
    }

    /** @param array<string,string> $errors */
    private function invalid(array $errors, int $code = 422)
    {
        return $this->respond(['status' => $code, 'message' => 'Validation failed', 'errors' => $errors], $code);
    }
}
