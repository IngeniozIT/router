<?php

namespace IngeniozIT\Router\Tests\Features;

use Exception;
use IngeniozIT\Http\Message\UriFactory;
use IngeniozIT\Router\InvalidRoute;
use IngeniozIT\Router\Route;
use IngeniozIT\Router\RouteGroup;
use IngeniozIT\Router\Tests\Fakes\TestMiddleware;
use IngeniozIT\Router\Tests\RouterCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class MiddlewaresTest extends RouterCase
{
    /**
     * @dataProvider providerMiddlewares
     */
    public function testRouteGroupCanHaveMiddlewares(mixed $middleware, string $expectedResponse): void
    {
        $routeGroup = new RouteGroup(
            routes: [
                Route::get(path: '/', callback: static fn(): ResponseInterface => self::response('TEST2')),
            ],
            middlewares: [$middleware],
        );
        $request = self::serverRequest('GET', '/');

        $response = $this->router($routeGroup)->handle($request);

        self::assertEquals($expectedResponse, (string) $response->getBody());
    }

    /**
     * @return array<string, array{middleware: mixed, expectedResponse: string}>
     */
    public static function providerMiddlewares(): array
    {
        return [
            'middleware that returns a response' => [
                'middleware' => TestMiddleware::class,
                'expectedResponse' => 'TEST',
            ],
            'middleware that forwards to handler' => [
                'middleware' => static fn(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface => $handler->handle($request),
                'expectedResponse' => 'TEST2',
            ],
        ];
    }

    public function testRouteGroupCanHaveMultipleMiddlewares(): void
    {
        $routeGroup = new RouteGroup(
            routes: [
                Route::get(path: '/', callback: static fn(): ResponseInterface => self::response('TEST2')),
            ],
            middlewares: [
                static fn(ServerRequestInterface $request, RequestHandlerInterface $handler) => $handler->handle($request),
                static fn(ServerRequestInterface $request, RequestHandlerInterface $handler) => throw new Exception(''),
            ],
        );
        $request = self::serverRequest('GET', '/');

        self::expectException(Exception::class);
        $response = $this->router($routeGroup)->handle($request);

        self::assertEquals('TEST', (string) $response->getBody());
    }

    /**
     * @dataProvider providerInvalidMiddlewares
     */
    public function testCannotExecuteInvalidMiddlewares(mixed $middleware): void
    {
        $routeGroup = new RouteGroup(
            routes: [
                Route::get(path: '/', callback: static fn(): ResponseInterface => self::response('TEST')),
            ],
            middlewares: [$middleware],
        );
        $request = self::serverRequest('GET', '/');

        self::expectException(Throwable::class);
        $this->router($routeGroup)->handle($request);
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function providerInvalidMiddlewares(): array
    {
        return [
            'not a middleware' => [UriFactory::class],
            'callable that does not return a response' => [static fn(): bool => true],
        ];
    }
}
