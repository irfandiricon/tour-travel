<?php

use App\Controllers\ApiAuthController;
use App\Controllers\ApiFaqController;
use App\Controllers\ApiFleetController;
use App\Controllers\ApiGalleryController;
use App\Controllers\ApiNewsController;
use App\Controllers\ApiSiteConfigController;
use CodeIgniter\HTTP\ResponseInterface;
use PHPUnit\Framework\TestCase;

/**
 * Example tests for proxy response-shape contracts.
 *
 * Validates: Requirements 9.3, 9.4
 *
 * @internal
 */
final class ProxyResponseShapeTest extends TestCase
{
    private const KEYS = ['status', 'message', 'data', 'total'];

    /** Stub Service returning a fixed successful upstream result. */
    private function stubService(): object
    {
        return new class () {
            public function ApiRequestWithHeaderToken($url, $body, $method)
            {
                return (object) [
                    'status'  => 200,
                    'message' => 'ok',
                    'data'    => [['id' => 1]],
                    'total'   => 1,
                ];
            }

            public function ApiRequesUploadWithHeaderToken($url, $body, $method)
            {
                return $this->ApiRequestWithHeaderToken($url, $body, $method);
            }
        };
    }

    /** Build a proxy without running its constructor, injecting the stub service. */
    private function makeProxy(string $class): object
    {
        $ref   = new ReflectionClass($class);
        $proxy = $ref->newInstanceWithoutConstructor();

        $this->setProp($ref, $proxy, 'service', $this->stubService());
        $this->setProp($ref, $proxy, 'request', service('request'));
        $this->setProp($ref, $proxy, 'response', service('response'));

        return $proxy;
    }

    private function setProp(ReflectionClass $ref, object $obj, string $name, $value): void
    {
        $prop = $ref->getProperty($name);
        $prop->setAccessible(true);
        $prop->setValue($obj, $value);
    }

    public static function ajaxProvider(): array
    {
        return [
            'news list'            => [ApiNewsController::class, 'apiList', []],
            'news create'          => [ApiNewsController::class, 'apiCreate', []],
            'news deactivate'      => [ApiNewsController::class, 'apiDeactivate', [1]],
            'faq list'             => [ApiFaqController::class, 'apiList', []],
            'faq delete'           => [ApiFaqController::class, 'apiDelete', [1]],
            'fleet list'           => [ApiFleetController::class, 'apiList', []],
            'fleet delete'         => [ApiFleetController::class, 'apiDelete', [1]],
            'fleet activate'       => [ApiFleetController::class, 'apiActivate', [1]],
            'gallery delete'       => [ApiGalleryController::class, 'apiDelete', [1]],
            'site config save'     => [ApiSiteConfigController::class, 'apiSave', []],
            'auth login json'      => [ApiAuthController::class, 'apiLoginJson', []],
        ];
    }

    /**
     * @dataProvider ajaxProvider
     */
    public function testAjaxMethodsReturnJsonEnvelope(string $class, string $method, array $args): void
    {
        $proxy = $this->makeProxy($class);

        $result = $proxy->{$method}(...$args);

        $this->assertInstanceOf(ResponseInterface::class, $result);

        $body = json_decode($result->getBody(), true);
        $this->assertIsArray($body);
        foreach (self::KEYS as $key) {
            $this->assertArrayHasKey($key, $body, "{$class}::{$method} missing '{$key}'");
        }
        $this->assertSame(200, $body['status']);
        $this->assertSame('ok', $body['message']);
        $this->assertSame([['id' => 1]], $body['data']);
        $this->assertSame(1, $body['total']);
    }

    public static function arrayProvider(): array
    {
        return [
            'news show'          => [ApiNewsController::class, 'apiShow', [1]],
            'news active'        => [ApiNewsController::class, 'apiListActive', []],
            'faq show'           => [ApiFaqController::class, 'apiShow', [1]],
            'faq active'         => [ApiFaqController::class, 'apiListActive', []],
            'fleet show'         => [ApiFleetController::class, 'apiShow', [1]],
            'fleet active'       => [ApiFleetController::class, 'apiListActive', []],
            'gallery active'     => [ApiGalleryController::class, 'apiListActive', []],
            'site config get'    => [ApiSiteConfigController::class, 'apiGetConfig', []],
            'auth login'         => [ApiAuthController::class, 'apiLogin', [['username' => 'a', 'password' => 'b']]],
        ];
    }

    /**
     * @dataProvider arrayProvider
     */
    public function testPhpConsumedMethodsReturnArray(string $class, string $method, array $args): void
    {
        $proxy = $this->makeProxy($class);

        $result = $proxy->{$method}(...$args);

        $this->assertIsArray($result);
        $this->assertNotInstanceOf(ResponseInterface::class, $result);
        foreach (self::KEYS as $key) {
            $this->assertArrayHasKey($key, $result, "{$class}::{$method} missing '{$key}'");
        }
        $this->assertSame(200, $result['status']);
        $this->assertSame(1, $result['total']);
    }
}
