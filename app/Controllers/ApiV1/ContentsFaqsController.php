<?php

namespace App\Controllers\ApiV1;

use App\Libraries\AdminSearch;
use App\Libraries\ContentValidator;
use App\Models\FaqModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;

/**
 * api/v1 FAQ endpoints (Req 8.1-8.4, 8.6).
 * Owns validation and persistence; extends Controller (not BaseController).
 */
class ContentsFaqsController extends Controller
{
    use ResponseTrait;

    protected $faqModel;

    /** Columns the admin search looks in. */
    public const SEARCH_COLUMNS = ['question_en', 'question_id', 'question_ms', 'answer_en', 'answer_id', 'answer_ms', 'status'];

    private const FIELDS = [
        'question_en', 'question_id', 'question_ms',
        'answer_en', 'answer_id', 'answer_ms',
    ];

    public function __construct()
    {
        $this->faqModel = new FaqModel();
    }

    /** GET contents-faqs?per_page=&page= (paginated, non-deleted). */
    public function index()
    {
        $perPage = max(1, min(100, (int) ($this->request->getGet('per_page') ?: 10)));
        $page    = max(1, (int) ($this->request->getGet('page') ?: 1));

        $search = AdminSearch::term($this->request->getGet('search'));
        $total  = AdminSearch::apply((new FaqModel())->notDeleted(), $search, self::SEARCH_COLUMNS)->countAllResults();
        $rows   = AdminSearch::apply((new FaqModel())->notDeleted(), $search, self::SEARCH_COLUMNS)
            ->orderBy('id', 'DESC')
            ->findAll($perPage, ($page - 1) * $perPage);

        return $this->response->setJSON([
            'status'   => 200,
            'message'  => 'OK',
            'data'     => $rows,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
        ]);
    }

    /** GET contents-faqs/active (published only). */
    public function getAllActive()
    {
        $rows = (new FaqModel())->published()->orderBy('id', 'ASC')->findAll();

        return $this->response->setJSON([
            'status'  => 200,
            'message' => 'OK',
            'data'    => $rows,
            'total'   => count($rows),
        ]);
    }

    /** GET contents-faqs/(:num) */
    public function show($id = null)
    {
        $row = $this->faqModel->findActive((int) $id);
        if ($row === null) {
            return $this->notFound();
        }

        return $this->response->setJSON(['status' => 200, 'message' => 'OK', 'data' => $row, 'total' => 1]);
    }

    /** POST contents-faqs */
    public function create()
    {
        $input  = $this->payload();
        $errors = $this->validateInput($input);
        if ($errors) {
            return $this->invalid($errors);
        }

        $record           = $this->pick($input);
        $record['status'] = $this->status($input['status'] ?? null) ?? 'draft';

        $id = $this->faqModel->insert($record, true);
        if (! $id) {
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 500, 'message' => 'Failed to save FAQ', 'data' => [], 'total' => 0,
            ]);
        }

        return $this->response->setStatusCode(201)->setJSON([
            'status'  => 201,
            'message' => 'FAQ created',
            'data'    => ['id' => (int) $id, 'status' => $record['status']],
            'total'   => 1,
        ]);
    }

    /** PUT/POST contents-faqs/(:num) */
    public function update($id = null)
    {
        $existing = $this->faqModel->findActive((int) $id);
        if ($existing === null) {
            return $this->notFound();
        }

        $input  = $this->payload();
        $merged = array_merge($existing, $this->pick($input));
        $errors = $this->validateInput($merged);
        if ($errors) {
            return $this->invalid($errors);
        }

        $record = $this->pick($input);
        $status = $this->status($input['status'] ?? null);
        if ($status !== null) {
            $record['status'] = $status;
        }

        if ($record) {
            $this->faqModel->update((int) $id, $record);
        }

        return $this->response->setJSON([
            'status'  => 200,
            'message' => 'FAQ updated',
            'data'    => ['id' => (int) $id],
            'total'   => 1,
        ]);
    }

    /** DELETE contents-faqs/(:num) (soft delete). */
    public function delete($id = null)
    {
        if ($this->faqModel->findActive((int) $id) === null) {
            return $this->notFound();
        }

        $this->faqModel->softDelete((int) $id);

        return $this->response->setJSON([
            'status'  => 200,
            'message' => 'FAQ deleted',
            'data'    => ['id' => (int) $id],
            'total'   => 1,
        ]);
    }

    /** @return array<string,string> */
    private function validateInput(array $data): array
    {
        return array_merge(
            ContentValidator::validateFieldLengths($data, ContentValidator::LIMITS_FAQ, false),
            ContentValidator::validateRequiredEn($data, ['question', 'answer'])
        );
    }

    private function payload(): array
    {
        // getJSON() throws on a non-JSON body (the admin forms post FormData), so only parse JSON requests.
        $json = str_contains(strtolower($this->request->getHeaderLine('Content-Type')), 'json') ? $this->request->getJSON(true) : null;
        if (is_array($json) && $json) {
            return $json;
        }

        return $this->request->getPost() ?: [];
    }

    private function pick(array $input): array
    {
        $out = [];
        foreach (self::FIELDS as $f) {
            if (array_key_exists($f, $input)) {
                $v       = $input[$f];
                $out[$f] = is_scalar($v) ? (string) $v : null;
                if ($out[$f] === '' && $f !== 'question_en' && $f !== 'answer_en') {
                    $out[$f] = null;
                }
            }
        }

        return $out;
    }

    private function status($value): ?string
    {
        return in_array($value, ['draft', 'published'], true) ? $value : null;
    }

    private function notFound()
    {
        return $this->response->setStatusCode(404)->setJSON([
            'status' => 404, 'message' => 'FAQ not found', 'data' => [], 'total' => 0,
        ]);
    }

    private function invalid(array $errors)
    {
        return $this->response->setStatusCode(422)->setJSON([
            'status'  => 422,
            'message' => 'Validation failed: ' . implode('; ', $errors),
            'errors'  => $errors,
            'data'    => [],
            'total'   => 0,
        ]);
    }
}
