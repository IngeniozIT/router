<?php

declare(strict_types=1);

namespace IngeniozIT\Router\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use IngeniozIT\Router\{
    RouteGroup,
    Router,
    Route,
    EmptyRouteStack,
    InvalidRoute,
};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use Psr\Http\Message\ServerRequestInterface;
use IngeniozIT\Router\Tests\Fakes\{TestHandler, TestMiddleware};
use IngeniozIT\Http\Message\UriFactory;
use Closure;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class RouterTest extends TestCase
{
    use PsrTrait;

    private function router(RouteGroup $routeGroup, ?Closure $fallback = null): Router
    {
        return new Router($routeGroup, self::container(), $fallback);
    }

    /**
     * @dataProvider providerCallbacks
     */
    public function testCanExecuteARouteCallback(Closure|MiddlewareInterface|RequestHandlerInterface|string $callback): void
    {
        $routeGroup = new RouteGroup(routes: [
            Route::get(path: '/', callback: $callback),
        ]);
        $request = self::serverRequest('GET', '/');

        $response = $this->router($routeGroup)->handle($request);

        self::assertEquals('TEST', (string) $response->getBody());
    }

    /**
     * @return array<string, array{0: Closure|MiddlewareInterface|RequestHandlerInterface|string}>
     */
    public static function providerCallbacks(): array
    {
        return [
            'RequestHandler' => [new TestHandler(self::responseFactory(), self::streamFactory())],
            'RequestHandler callable' => [static fn(ServerRequestInterface $request): ResponseInterface => self::response('TEST')],
            'RequestHandler DI Container name' => [TestHandler::class],
            'Middleware' => [new TestMiddleware(self::responseFactory(), self::streamFactory())],
            'Middleware callable' => [static fn(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface => self::response('TEST')],
            'Middleware DI Container name' => [TestMiddleware::class],
        ];
    }

    public function testCannotExecuteAnInvalidRouteCallback(): void
    {
        $routeGroup = new RouteGroup(routes: [
            Route::get(path: '/', callback: UriFactory::class),
        ]);
        $request = self::serverRequest('GET', '/');

        self::expectException(InvalidRoute::class);
        $this->router($routeGroup)->handle($request);
    }

    public function testFiltersOutRoutesWithWrongPath(): void
    {
        $routeGroup = new RouteGroup(routes: [
            Route::get(path: '/test', callback: static fn(): ResponseInterface => self::response('TEST')),
            Route::get(path: '/test2', callback: static fn(): ResponseInterface => self::response('TEST2')),
        ]);
        $request = self::serverRequest('GET', '/test2');

        $response = $this->router($routeGroup)->handle($request);

        self::assertEquals('TEST2', (string) $response->getBody());
    }

    public function testFiltersOutRoutesWithWrongMethod(): void
    {
        $routeGroup = new RouteGroup(routes: [
            Route::get(path: '/', callback: static fn(): ResponseInterface => self::response('TEST')),
            Route::post(path: '/', callback: static fn(): ResponseInterface => self::response('TEST2')),
        ]);
        $request = self::serverRequest('POST', '/');

        $response = $this->router($routeGroup)->handle($request);

        self::assertEquals('TEST2', (string) $response->getBody());
    }

    public function testAddsMatchedParametersToRequest(): void
    {
        $routeGroup = new RouteGroup(routes: [
            Route::get(
                path: '/{foo}',
                callback: static fn(ServerRequestInterface $request): ResponseInterface =>
                self::response(var_export($request->getAttribute('foo'), true))
            ),
        ]);
        $request = self::serverRequest('GET', '/bar');

        $response = $this->router($routeGroup)->handle($request);

        self::assertEquals("'bar'", (string) $response->getBody());
    }

    public function testMustFindARouteToProcess(): void
    {
        $routeGroup = new RouteGroup(routes: [
            Route::get(path: '/foo', callback: static fn(): ResponseInterface => self::response('TEST')),
            Route::get(path: '/bar', callback: static fn(): ResponseInterface => self::response('TEST2')),
        ]);
        $request = self::serverRequest('GET', '/');

        self::expectException(EmptyRouteStack::class);
        $this->router($routeGroup)->handle($request);
    }

    public function testCanHaveAFallbackRoute(): void
    {
        $routeGroup = new RouteGroup(routes: []);
        $request = self::serverRequest('GET', '/');

        $response = $this->router($routeGroup, static fn(): ResponseInterface => self::response('TEST'))->handle($request);

        self::assertEquals('TEST', (string) $response->getBody());
    }

    /**
     * @dataProvider providerMiddlewares
     */
    public function testCanHaveAMiddlewares(Closure $middleware, string $expectedResponse): void
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
     * @return array<string, array{middleware: Closure, expectedResponse: string}>
     */
    public static function providerMiddlewares(): array
    {
        return [
            'middleware that returns a response' => [
                'middleware' => static fn(): ResponseInterface => self::response('TEST'),
                'expectedResponse' => 'TEST',
            ],
            'middleware that forwards to handler' => [
                'middleware' => static fn(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface => $handler->handle($request),
                'expectedResponse' => 'TEST2',
            ],
        ];
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

        $response = $this->router($routeGroup, static fn(): ResponseInterface => self::response('TEST'))->handle($request);

        self::assertEquals($expectedResponse, (string) $response->getBody());
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

    public function testAddsConditionsParametersToRequest(): void
    {
        $routeGroup = new RouteGroup(
            routes: [
                Route::get(
                    path: '/',
                    callback: static fn(ServerRequestInterface $request): ResponseInterface =>
                    self::response(var_export($request->getAttribute('foo'), true))
                ),
            ],
            conditions: [
                static fn(): array => ['foo' => 'bar'],
            ],
        );
        $request = self::serverRequest('GET', '/');

        $response = $this->router($routeGroup)->handle($request);

        self::assertEquals("'bar'", (string) $response->getBody());
    }
}
