<?php

declare(strict_types=1);

namespace IngeniozIT\Router\Tests;

use PHPUnit\Framework\TestCase;
use IngeniozIT\Router\Route;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
final class RouteTest extends TestCase
{
    use PsrTrait;

    /**
     * @dataProvider providerMethodsAndRoutes
     */
    public function testMatchesRequestBasedOnMethod(string $method, callable $routeCallable): void
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
    public function testCanMatchAnyMethod(string $method): void
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

    public function testMatchesRequestBasedOnPath(): void
    {
        $route = Route::get('/foo', 'foo');
        $matchingRequest = self::serverRequest('GET', '/foo');
        $nonMatchingRequest = self::serverRequest('GET', '/bar');

        $matchingResult = $route->match($matchingRequest);
        $nonMatchingResult = $route->match($nonMatchingRequest);

        self::assertSame([], $matchingResult);
        self::assertSame(false, $nonMatchingResult);
    }

    public function testExtractsParametersFromPath(): void
    {
        $route = Route::get('/foo/{bar}', 'foo');
        $request = self::serverRequest('GET', '/foo/baz');

        $result = $route->match($request);

        self::assertSame(['bar' => 'baz'], $result);
    }

    /**
     * @dataProvider providerRoutePatterns
     */
    public function testCanUseCustomPatterns(Route $route): void
    {
        $matchingRequest = self::serverRequest('GET', '/foo/123/456');
        $nonMatchingRequest = self::serverRequest('GET', '/foo/baz1/baz2');

        $matchingResult = $route->match($matchingRequest);
        $nonMatchingResult = $route->match($nonMatchingRequest);

        self::assertSame(['bar' => '123', 'baz' => '456'], $matchingResult);
        self::assertSame(false, $nonMatchingResult);
    }

    /**
     * @return array<string, array{0: Route}>
     */
    public static function providerRoutePatterns(): array
    {
        return [
            'from the path' => [Route::get('/foo/{bar:\d+}/{baz:\d+}', 'foo')],
            'from the patterns parameter' => [Route::get('/foo/{bar}/{baz}', 'foo', patterns: ['bar' => '\d+', 'baz' => '\d+'])],
            'path takes precedence over the patterns parameter' => [Route::get('/foo/{bar:\d+}/{baz:\d+}', 'foo', patterns: ['bar' => '[a-z]+', 'baz' => '\d+'])],
        ];
    }

    public function testCanBeNamed(): void
    {
        $route = Route::get('/foo', 'foo', 'route name');

        self::assertEquals('route name', $route->name);
    }
}
