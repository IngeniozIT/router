<?php

namespace IngeniozIT\Router\Tests;

use Closure;
use IngeniozIT\Http\Message\UriFactory;
use IngeniozIT\Router\Exception\InvalidRouteCondition;
use IngeniozIT\Router\Route;
use IngeniozIT\Router\RouteGroup;
use IngeniozIT\Router\Tests\Utils\RouterCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ConditionsTest extends RouterCase
{
    #[DataProvider('providerConditions')]
    public function testRouteGroupsCanHaveConditions(Closure $condition, string $expectedResponse): void
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

    public function testRouteGroupsCanHaveMultipleConditions(): void
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

    public function testConditionsCanAddAttributesToARequest(): void
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

    #[DataProvider('providerInvalidConditions')]
    public function testRouterCannotExecuteInvalidConditions(mixed $condition): void
    {
        $routeGroup = new RouteGroup(
            routes: [
                Route::get(path: '/', callback: static fn(): ResponseInterface => self::response('TEST')),
            ],
            conditions: [$condition],
        );
        $request = self::serverRequest('GET', '/');

        self::expectException(InvalidRouteCondition::class);
        $this->router($routeGroup)->handle($request);
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function providerInvalidConditions(): array
    {
        return [
            'not a callable' => [UriFactory::class],
            'callable that does not return bool or array' => [static fn(): bool => true],
        ];
    }
}
