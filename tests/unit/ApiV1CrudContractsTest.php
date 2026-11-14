<?php

use App\Controllers\ApiV1\ContentsFaqsController;
use App\Controllers\ApiV1\ContentsFleetController;
use App\Controllers\ApiV1\ContentsNewsController;
use App\Controllers\ApiV1\SiteConfigController;
use App\Libraries\WhatsAppLinkBuilder;
use App\Models\FaqModel;
use App\Models\FleetModel;
use App\Models\NewsModel;
use App\Models\SiteConfigModel;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\SiteURI;
use CodeIgniter\HTTP\UserAgent;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * In-memory storage used by model doubles (no database needed).
 */
trait InMemoryModelTrait
{
    public array $rows   = [];
    private int $nextId  = 1;

    public function insert($row = null, bool $returnID = true)
    {
        $id            = $this->nextId++;
        $this->rows[$id] = array_merge(['id' => $id, 'status' => 'draft', 'deleted_at' => null], (array) $row);

        return $returnID ? $id : true;
    }

    public function find($id = null)
    {
        return $this->rows[(int) $id] ?? null;
    }

    public function findActive(int $id): ?array
    {
        $row = $this->rows[$id] ?? null;

        return ($row === null || $row['status'] === 'deleted') ? null : $row;
    }

    public function update($id = null, $row = null): bool
    {
        if (! isset($this->rows[(int) $id])) {
            return false;
        }
        $this->rows[(int) $id] = array_merge($this->rows[(int) $id], (array) $row);

        return true;
    }

    public function softDelete(int $id): bool
    {
        if (! isset($this->rows[$id])) {
            return false;
        }
        $this->rows[$id]['status'] = 'deleted';

        return true;
    }
}

/**
 * Example tests for the api/v1 CRUD contracts, run against in-memory model doubles.
 *
 * @internal
 */
final class ApiV1CrudContractsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Model constructors build (but never open) a DB connection object. The default
        // test group uses SQLite3, which is not installed here; MySQLi is, and since no
        // query is ever run against the doubles, no real connection is made.
        $db = config('Database');
        if (extension_loaded('mysqli') && ! extension_loaded('sqlite3')) {
            $db->tests['DBDriver'] = 'MySQLi';
        }
    }

    /** Build an initialised controller carrying a JSON request body. */
    private function boot(object $controller, array $body = []): object
    {
        $config  = config('App');
        $request = new IncomingRequest($config, new SiteURI($config), json_encode($body), new UserAgent());
        $request->setHeader('Content-Type', 'application/json');
        $controller->initController($request, new Response($config), new NullLogger());

        return $controller;
    }

    private function inject(object $controller, string $property, object $model): void
    {
        $ref = new ReflectionProperty($controller, $property);
        $ref->setAccessible(true);
        $ref->setValue($controller, $model);
    }

    private function json($response): array
    {
        return json_decode($response->getBody(), true);
    }

    private function newsModel(): NewsModel
    {
        return new class() extends NewsModel {
            use InMemoryModelTrait;
        };
    }

    private function fleetModel(): FleetModel
    {
        return new class() extends FleetModel {
            use InMemoryModelTrait;
        };
    }

    private function faqModel(): FaqModel
    {
        return new class() extends FaqModel {
            use InMemoryModelTrait;
        };
    }

    private function siteConfigModel(): SiteConfigModel
    {
        return new class() extends SiteConfigModel {
            public ?array $row = null;

            public function getConfig(): ?array
            {
                return $this->row;
            }

            public function insert($row = null, bool $returnID = true)
            {
                $this->row = array_merge(['id' => 1], (array) $row);

                return 1;
            }

            public function update($id = null, $row = null): bool
            {
                $this->row = array_merge($this->row ?? ['id' => 1], (array) $row);

                return true;
            }

            public function find($id = null)
            {
                return $this->row;
            }
        };
    }

    // ---------------------------------------------------------------
    // Create returns id + status (Req 4.1, 6.1, 6.5, 6.6, 8.1)
    // ---------------------------------------------------------------

    public function testFleetCreateReturnsIdAndStatus(): void
    {
        $c = $this->boot(new ContentsFleetController(), ['name_en' => 'Van']);
        $this->inject($c, 'model', $this->fleetModel());

        $res  = $c->create();
        $body = $this->json($res);

        $this->assertSame(201, $res->getStatusCode());
        $this->assertSame(1, $body['data']['id']);
        $this->assertSame('draft', $body['data']['status']);
    }

    public function testFaqCreateReturnsIdAndStatus(): void
    {
        $c = $this->boot(new ContentsFaqsController(), ['question_en' => 'Q?', 'answer_en' => 'A.']);
        $this->inject($c, 'faqModel', $this->faqModel());

        $res  = $c->create();
        $body = $this->json($res);

        $this->assertSame(201, $res->getStatusCode());
        $this->assertSame(1, $body['data']['id']);
        $this->assertSame('draft', $body['data']['status']);
    }

    public function testNewsCreateReturnsIdAndStatus(): void
    {
        $c = $this->boot(new ContentsNewsController(), [
            'title_en' => 'T', 'title_id' => 'T', 'title_ms' => 'T',
            'body_en'  => 'B', 'body_id'  => 'B', 'body_ms'  => 'B',
        ]);
        $this->inject($c, 'newsModel', $this->newsModel());

        $res  = $c->create();
        $body = $this->json($res);

        $this->assertSame(201, $res->getStatusCode());
        $this->assertSame(1, $body['data']['id']);
        $this->assertSame('draft', $body['data']['status']);
    }

    public function testSiteConfigSaveReturnsSavedConfiguration(): void
    {
        $c = $this->boot(new SiteConfigController(), $this->validConfig(['whatsapp_number' => '+628123456789']));
        $this->inject($c, 'siteConfigModel', $this->siteConfigModel());

        $res  = $c->save();
        $body = $this->json($res);

        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame(1, $body['data']['id']);
        $this->assertSame('+628123456789', $body['data']['whatsapp_number']);
    }

    // ---------------------------------------------------------------
    // Delete returns 200 (Req 4.4, 5.4, 8.2)
    // ---------------------------------------------------------------

    public function testNewsDeleteReturns200(): void
    {
        $model = $this->newsModel();
        $id    = $model->insert(['title_en' => 'T', 'body_en' => 'B']);
        $c     = $this->boot(new ContentsNewsController());
        $this->inject($c, 'newsModel', $model);

        $this->assertSame(200, $c->delete($id)->getStatusCode());
        $this->assertNull($model->findActive($id));
    }

    public function testFaqDeleteReturns200(): void
    {
        $model = $this->faqModel();
        $id    = $model->insert(['question_en' => 'Q', 'answer_en' => 'A']);
        $c     = $this->boot(new ContentsFaqsController());
        $this->inject($c, 'faqModel', $model);

        $this->assertSame(200, $c->delete($id)->getStatusCode());
        $this->assertNull($model->findActive($id));
    }

    // ---------------------------------------------------------------
    // Not found returns 404 (Req 4.6, 5.6, 8.3)
    // ---------------------------------------------------------------

    public function testNewsNotFoundReturns404(): void
    {
        $c = $this->boot(new ContentsNewsController(), ['title_en' => 'T', 'body_en' => 'B']);
        $this->inject($c, 'newsModel', $this->newsModel());

        $this->assertSame(404, $c->show(999)->getStatusCode());
        $this->assertSame(404, $c->update(999)->getStatusCode());
        $this->assertSame(404, $c->delete(999)->getStatusCode());
    }

    public function testFaqNotFoundReturns404(): void
    {
        $c = $this->boot(new ContentsFaqsController());
        $this->inject($c, 'faqModel', $this->faqModel());

        $this->assertSame(404, $c->show(999)->getStatusCode());
        $this->assertSame(404, $c->delete(999)->getStatusCode());
    }

    public function testDeletedRecordIsThenNotFound(): void
    {
        $model = $this->faqModel();
        $id    = $model->insert(['question_en' => 'Q', 'answer_en' => 'A']);
        $c     = $this->boot(new ContentsFaqsController());
        $this->inject($c, 'faqModel', $model);

        $this->assertSame(200, $c->delete($id)->getStatusCode());
        $this->assertSame(404, $c->delete($id)->getStatusCode());
    }

    // ---------------------------------------------------------------
    // Validation 422 (Req 4.1, 8.1)
    // ---------------------------------------------------------------

    public function testFaqCreateWithEmptyEnReturns422(): void
    {
        $model = $this->faqModel();
        $c     = $this->boot(new ContentsFaqsController(), ['question_en' => '', 'answer_en' => '']);
        $this->inject($c, 'faqModel', $model);

        $res = $c->create();

        $this->assertSame(422, $res->getStatusCode());
        $this->assertArrayHasKey('question_en', $this->json($res)['errors']);
        $this->assertArrayHasKey('answer_en', $this->json($res)['errors']);
        $this->assertSame([], $model->rows);
    }

    public function testFaqQuestionOverLimitReturns422(): void
    {
        $c = $this->boot(new ContentsFaqsController(), ['question_en' => str_repeat('q', 501), 'answer_en' => 'A']);
        $this->inject($c, 'faqModel', $this->faqModel());

        $this->assertSame(422, $c->create()->getStatusCode());
    }

    public function testSiteConfigMissingLanguageReturns422AndKeepsPrevious(): void
    {
        $model      = $this->siteConfigModel();
        $model->row = array_merge(['id' => 1], $this->validConfig());
        $previous   = $model->row;

        $c = $this->boot(new SiteConfigController(), ['hero_title_ms' => '']);
        $this->inject($c, 'siteConfigModel', $model);

        $res = $c->save();

        $this->assertSame(422, $res->getStatusCode());
        $this->assertArrayHasKey('hero_title_ms', $this->json($res)['errors']);
        $this->assertSame($previous, $model->row);
    }

    // ---------------------------------------------------------------
    // Empty WhatsApp number saves and disables booking (Req 7.4)
    // ---------------------------------------------------------------

    public function testEmptyWhatsappNumberSavesAndDisablesBooking(): void
    {
        $model = $this->siteConfigModel();
        $c     = $this->boot(new SiteConfigController(), $this->validConfig(['whatsapp_number' => '']));
        $this->inject($c, 'siteConfigModel', $model);

        $res  = $c->save();
        $body = $this->json($res);

        $this->assertSame(200, $res->getStatusCode());
        $this->assertNull($body['data']['whatsapp_number']);
        $this->assertNull(WhatsAppLinkBuilder::build($body['data']['whatsapp_number'], 'Van'));
    }

    public function testInvalidWhatsappNumberReturns422(): void
    {
        $c = $this->boot(new SiteConfigController(), $this->validConfig(['whatsapp_number' => '0812']));
        $this->inject($c, 'siteConfigModel', $this->siteConfigModel());

        $res = $c->save();

        $this->assertSame(422, $res->getStatusCode());
        $this->assertArrayHasKey('whatsapp_number', $this->json($res)['errors']);
    }

    /** @return array<string,string> */
    private function validConfig(array $override = []): array
    {
        $cfg = [];
        foreach (['hero_title', 'hero_subtitle', 'about_body', 'gallery_title', 'contact_body'] as $base) {
            foreach (['en', 'id', 'ms'] as $lang) {
                $cfg["{$base}_{$lang}"] = "{$base} {$lang}";
            }
        }

        return array_merge($cfg, $override);
    }
}
