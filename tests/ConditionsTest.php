<?php

namespace IngeniozIT\Router\Tests;

use Closure;
use IngeniozIT\Http\Message\UriFactory;
use IngeniozIT\Router\Condition\Exception\InvalidConditionHandler;
use IngeniozIT\Router\Condition\Exception\InvalidConditionResponse;
use IngeniozIT\Router\Route;
use IngeniozIT\Router\RouteGroup;
use IngeniozIT\Router\Tests\Utils\RouterCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

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

        $response = $this->router($routeGroup, static fn(): ResponseInterface => self::response('TEST'))->handle(
            $request
        );

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

        $response = $this->router($routeGroup, static fn(): ResponseInterface => self::response('TEST'))->handle(
            $request
        );

        self::assertEquals('TEST', (string)$response->getBody());
    }

    public function testConditionsCanAddAttributesToARequest(): void
    {
        $routeGroup = new RouteGroup(
            routes: [
                Route::get(
                    path: '/',
                    callback: static fn(ServerRequestInterface $request): ResponseInterface => self::response(
                        var_export($request->getAttribute('foo'), true)
                    )
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

    /**
     * @param class-string<Throwable> $expectedException
     */
    #[DataProvider('providerInvalidConditions')]
    public function testRouterCannotExecuteInvalidConditions(
        mixed $condition,
        string $expectedException,
        string $expectedMessage,
    ): void {
        $routeGroup = new RouteGroup(
            routes: [
                Route::get(path: '/', callback: static fn(): ResponseInterface => self::response('TEST')),
            ],
            conditions: [$condition],
        );
        $request = self::serverRequest('GET', '/');

        self::expectException($expectedException);
        self::expectExceptionMessage($expectedMessage);
        $this->router($routeGroup)->handle($request);
    }

    /**
     * @return array<string, array{mixed, class-string<Throwable>, string}>
     */
    public static function providerInvalidConditions(): array
    {
        return [
            'not a callable' => [
                UriFactory::class,
                InvalidConditionHandler::class,
                'Condition handler must be a callable, IngeniozIT\Http\Message\UriFactory given.',
            ],
            'callable that does not return bool or array' => [
                static fn(): bool => true,
                InvalidConditionResponse::class,
                'Condition must either return an array or false, bool given.',
            ],
        ];
    }
}
