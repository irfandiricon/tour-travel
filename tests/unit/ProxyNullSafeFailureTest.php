<?php

use App\Controllers\ApiNewsController;
use App\Libraries\ProxyResponseMapper;
use App\Libraries\Service;
use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ProxyNullSafeFailureTest extends TestCase
{
    use TestTrait;

    /**
     * Generator of arbitrary failure results a Service/cURL call may produce.
     */
    private function failureResult(): Generator
    {
        $badStatus = Generator\oneOf(
            Generator\choose(-50, 199),
            Generator\choose(300, 700),
            Generator\constant(null),
            Generator\constant('abc'),
            Generator\constant('')
        );

        return Generator\oneOf(
            Generator\constant(null),
            Generator\constant([]),
            Generator\constant(new stdClass()),
            Generator\constant(''),
            Generator\constant(false),
            Generator\string(),
            Generator\int(),
            Generator\map(static fn ($s) => $s, Generator\string()),
            // object / array with a non-2xx or unusable status
            Generator\map(
                static fn (array $t) => (object) ['status' => $t[0], 'message' => $t[1], 'data' => []],
                Generator\tuple($badStatus, Generator\string())
            ),
            Generator\map(
                static fn (array $t) => ['status' => $t[0], 'messages' => ['error' => $t[1]]],
                Generator\tuple($badStatus, Generator\string())
            ),
            // no status key at all
            Generator\map(
                static fn (string $s) => (object) ['message' => $s, 'data' => ['x' => 1]],
                Generator\string()
            )
        );
    }

    private function assertFailureEnvelope(array $mapped): void
    {
        $this->assertIsInt($mapped['status']);
        $this->assertTrue($mapped['status'] < 200 || $mapped['status'] > 299, 'status must be non-2xx');
        $this->assertStringContainsString(ProxyResponseMapper::FAILURE_MESSAGE, $mapped['message']);
        $this->assertSame([], $mapped['data']);
        $this->assertSame(0, $mapped['total']);
    }

    // Feature: travel-rental-website, Property 10: Proxy null-safe failure mapping
    /** Validates: Requirements 4.7, 5.7, 9.5 */
    public function testMapperFailureResultsNeverSucceed(): void
    {
        $this->limitTo(100)
            ->forAll($this->failureResult())
            ->then(function ($result): void {
                $this->assertFailureEnvelope(ProxyResponseMapper::map($result));
            });
    }

    // Feature: travel-rental-website, Property 10: Proxy null-safe failure mapping
    /** Validates: Requirements 4.7, 5.7, 9.5 */
    public function testProxyWithStubbedServiceMapsFailures(): void
    {
        $this->limitTo(100)
            ->forAll($this->failureResult(), Generator\elements('show', 'active'))
            ->then(function ($result, string $action): void {
                $stub = new class ($result) extends Service {
                    public function __construct(private $result)
                    {
                    }

                    public function ApiRequestWithHeaderToken($url, $json = '', $method = 'POST')
                    {
                        return $this->result;
                    }
                };

                $ref        = new ReflectionClass(ApiNewsController::class);
                $controller = $ref->newInstanceWithoutConstructor();
                $prop       = $ref->getProperty('service');
                $prop->setAccessible(true);
                $prop->setValue($controller, $stub);

                $mapped = $action === 'show' ? $controller->apiShow(1) : $controller->apiListActive();

                $this->assertFailureEnvelope($mapped);
            });
    }
}
