<?php

declare(strict_types=1);

namespace IngeniozIT\Router\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use IngeniozIT\Router\{Router, RouteGroup, Route, InvalidRoute};
use IngeniozIT\Http\Message\UriFactory;
use Closure;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
final class RouteGroupConditionTest extends TestCase
{
    use PsrTrait;

    private function router(RouteGroup $routeGroup, ?Closure $fallback = null): Router
    {
        return new Router($routeGroup, self::container(), $fallback);
    }

    /**
     * @dataProvider providerConditions
     */
    public function testCanHaveConditions(Closure $condition, string $expectedResponse): void
    {
        $routeGroup = new RouteGroup(
            routes: [
                Route::get(path: '/', callback: static fn(): ResponseInterface => self::response('TEST2')),
            ],
            conditions: [$condition],
        );
        $request = self::serverRequest('GET', '/');

        $response = $this->router($routeGroup, static fn(): ResponseInterface =>
            self::response('TEST'))->handle($request);

        self::assertEquals($expectedResponse, (string)$response->getBody());
    }

    /**
     * @return array<string, array{condition: Closure, expectedResponse: string}>
     */
    public static function providerConditions(): array
    {
        return [
            'valid condition executes routes' => [
                'condition' => static fn(): array => [],
                'expectedResponse' => 'TEST2',
            ],
            'invalid condition executes fallback' => [
                'condition' => static fn(): bool => false,
                'expectedResponse' => 'TEST',
            ],
        ];
    }

    public function testCanHaveMultipleConditions(): void
    {
        $routeGroup = new RouteGroup(
            routes: [
                Route::get(path: '/', callback: static fn(): ResponseInterface => self::response('TEST2')),
            ],
            conditions: [
                static fn(): array => [],
                static fn(): bool => false,
            ],
        );
        $request = self::serverRequest('GET', '/');

        $response = $this->router($routeGroup, static fn(): ResponseInterface =>
        self::response('TEST'))->handle($request);

        self::assertEquals('TEST', (string)$response->getBody());
    }

    /**
     * @dataProvider providerInvalidConditions
     */
    public function testCannotExecuteInvalidConditions(mixed $condition): void
    {
        $routeGroup = new RouteGroup(
            routes: [
                Route::get(path: '/', callback: static fn(): ResponseInterface => self::response('TEST')),
            ],
            conditions: [$condition],
        );
        $request = self::serverRequest('GET', '/');

        self::expectException(InvalidRoute::class);
        $this->router($routeGroup)->handle($request);
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function providerInvalidConditions(): array
    {
        return [
            'not a callable' => [UriFactory::class],
            'callable that does not return array or false' => [static fn(): bool => true],
        ];
    }

    public function testAddsConditionParametersToRequest(): void
    {
        $routeGroup = new RouteGroup(
            routes: [
                Route::get(
                    path: '/',
                    callback: static fn(ServerRequestInterface $request): ResponseInterface => self::response(var_export($request->getAttribute('foo'), true))
                ),
            ],
            conditions: [
                static fn(): array => ['foo' => 'bar'],
            ],
        );
        $request = self::serverRequest('GET', '/');

        $response = $this->router($routeGroup)->handle($request);

        self::assertEquals("'bar'", (string)$response->getBody());
    }
}
