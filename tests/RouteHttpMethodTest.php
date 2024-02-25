<?php

declare(strict_types=1);

namespace IngeniozIT\Router\Tests;

use IngeniozIT\Router\Route;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class RouteHttpMethodTest extends TestCase
{
    use PsrTrait;

    /**
     * @dataProvider providerMethodsAndRoutes
     */
    public function testRouteMatchesRequestsBasedOnMethod(string $method, callable $routeCallable): void
    {
        /** @var Route $route */
        $route = $routeCallable('/', 'foo');
        $request = self::serverRequest($method, '/');

        $result = $route->match($request);

        self::assertSame([], $result);
    }

    /**
     * @return array<string, array{0: string, 1: callable}>
     */
    public static function providerMethodsAndRoutes(): array
    {
        return [
            'GET' => ['GET', Route::get(...)],
            'POST' => ['POST', Route::post(...)],
            'PUT' => ['PUT', Route::put(...)],
            'PATCH' => ['PATCH', Route::patch(...)],
            'DELETE' => ['DELETE', Route::delete(...)],
            'HEAD' => ['HEAD', Route::head(...)],
            'OPTIONS' => ['OPTIONS', Route::options(...)],
        ];
    }

    /**
     * @dataProvider providerRouteMethods
     */
    public function testRouteCanMatchAnyMethod(string $method): void
    {
        $route = Route::any('/', 'foo');
        $request = self::serverRequest($method, '/');

        $result = $route->match($request);

        self::assertSame([], $result);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function providerRouteMethods(): array
    {
        return [
            'GET' => ['GET'],
            'POST' => ['POST'],
            'PUT' => ['PUT'],
            'PATCH' => ['PATCH'],
            'DELETE' => ['DELETE'],
            'HEAD' => ['HEAD'],
            'OPTIONS' => ['OPTIONS'],
        ];
    }

    public function testCanMatchSomeMethods(): void
    {
        $route = Route::some(['GET', 'POST'], '/', 'foo');
        $getRequest = self::serverRequest('GET', '/');
        $postRequest = self::serverRequest('POST', '/');
        $putRequest = self::serverRequest('PUT', '/');

        $getResult = $route->match($getRequest);
        $postResult = $route->match($postRequest);
        $putResult = $route->match($putRequest);

        self::assertSame([], $getResult);
        self::assertSame([], $postResult);
        self::assertSame(false, $putResult);
    }

    public function testMethodNameCanBeLowercase(): void
    {
        $route = Route::some(['delete'], '/', 'foo');
        $deleteRequest = self::serverRequest('DELETE', '/');

        $deleteResult = $route->match($deleteRequest);

        self::assertSame([], $deleteResult);
    }
}
