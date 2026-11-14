<?php
use App\Controllers\ApiV1\SiteConfigController;
use App\Models\SiteConfigModel;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\SiteURI;
use CodeIgniter\HTTP\UserAgent;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Site configuration save completes within 5 seconds (no database; in-memory model double).
 *
 * Validates: Requirements 6.1
 *
 * @internal
 */
final class SiteConfigTimingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Model constructors build (but never open) a DB connection; see ApiV1CrudContractsTest.
        $db = config('Database');
        if (extension_loaded('mysqli') && ! extension_loaded('sqlite3')) {
            $db->tests['DBDriver'] = 'MySQLi';
        }
    }

    public function testSaveCompletesWithinFiveSeconds(): void
    {
        $model = new class() extends SiteConfigModel {
            public array $row = [];

            public function getConfig(): ?array
            {
                return $this->row === [] ? null : $this->row;
            }

            public function insert($row = null, bool $returnID = true)
            {
                $this->row = array_merge(['id' => 1], (array) $row);

                return 1;
            }

            public function update($id = null, $row = null): bool
            {
                $this->row = array_merge($this->row, (array) $row);

                return true;
            }

            public function find($id = null)
            {
                return $this->row ?: null;
            }
        };

        $body = ['whatsapp_number' => '+628123456789'];
        foreach (['hero_title', 'hero_subtitle', 'about_body', 'gallery_title', 'contact_body'] as $base) {
            foreach (['en', 'id', 'ms'] as $lang) {
                $body[$base . '_' . $lang] = "$base $lang";
            }
        }

        $config  = config('App');
        $request = new IncomingRequest($config, new SiteURI($config), json_encode($body), new UserAgent());
        $request->setHeader('Content-Type', 'application/json');

        $controller = new SiteConfigController();
        $controller->initController($request, new Response($config), new NullLogger());
        $prop = new ReflectionProperty($controller, 'siteConfigModel');
        $prop->setAccessible(true);
        $prop->setValue($controller, $model);

        $start    = microtime(true);
        $response = $controller->save();
        $elapsed  = microtime(true) - $start;

        $json = json_decode($response->getBody(), true);
        $this->assertSame(200, $json['status']);
        $this->assertLessThan(5.0, $elapsed);
    }
}
