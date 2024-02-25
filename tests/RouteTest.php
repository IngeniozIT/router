<?php

declare(strict_types=1);

namespace IngeniozIT\Router\Tests;

use PHPUnit\Framework\TestCase;
use IngeniozIT\Router\Route;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class RouteTest extends TestCase
{
    use PsrTrait;

    public function testMatchesRequestsBasedOnPath(): void
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
    public function testCanUseCustomParameterPatterns(Route $route): void
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
            'patterns inside the path' => [Route::get(
                path: '/foo/{bar:\d+}/{baz:\d+}',
                callback: 'foo'
            )],
            'patterns as a parameter' => [Route::get(
                path: '/foo/{bar}/{baz}',
                callback: 'foo',
                where: ['bar' => '\d+', 'baz' => '\d+'],
            )],
            'path takes precendence over parameters' => [Route::get(
                path: '/foo/{bar:\d+}/{baz:\d+}',
                callback: 'foo',
                where: ['bar' => '[a-z]+', 'baz' => '\d+'],
            )],
        ];
    }

    public function testCanBeNamed(): void
    {
        $route = Route::get('/foo', 'foo', 'route name');

        self::assertEquals('route name', $route->name);
    }

    public function testCanHaveAdditionalAttributes(): void
    {
        $route = Route::get('/foo', 'foo', with: ['foo' => 'bar']);

        self::assertEquals(['foo' => 'bar'], $route->with);
    }
}
