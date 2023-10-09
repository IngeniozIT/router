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
 */
final class RouterRouteTest extends TestCase
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
            'Route that returns a string' => [static fn(): string => 'TEST'],
        ];
    }

    /**
     * @dataProvider providerInvalidHandlers
     */
    public function testCannotExecuteAnInvalidRouteCallback(): void
    {
        $routeGroup = new RouteGroup(routes: [
            Route::get(path: '/', callback: UriFactory::class),
        ]);
        $request = self::serverRequest('GET', '/');

        self::expectException(InvalidRoute::class);
        $this->router($routeGroup)->handle($request);
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function providerInvalidHandlers(): array
    {
        return [
            'not a handler' => [UriFactory::class],
            'value that cannot be converted to a response' => [static fn(): array => ['foo' => 'bar']],
        ];
    }

    public function testFiltersOutNonMatchingRoutes(): void
    {
        $routeGroup = new RouteGroup(routes: [
            Route::get(path: '/test', callback: static fn(): ResponseInterface => self::response('KO')),
            Route::post(path: '/test2', callback: static fn(): ResponseInterface => self::response('KO')),
            Route::get(path: '/test2', callback: static fn(): ResponseInterface => self::response('OK')),
        ]);
        $request = self::serverRequest('GET', '/test2');

        $response = $this->router($routeGroup)->handle($request);

        self::assertEquals('OK', (string) $response->getBody());
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

    /**
     * @dataProvider providerRouteGroupsWithCustomParameters
     */
    public function testCanHaveCustomParameters(RouteGroup $routeGroup): void
    {
        $matchingRequest = self::serverRequest('GET', '/123');
        $nonMatchingRequest = self::serverRequest('GET', '/abc');

        $matchingResponse = $this->router($routeGroup)->handle($matchingRequest);
        $nonMatchingResponse = $this->router($routeGroup, static fn(): string => 'KO')->handle($nonMatchingRequest);

        self::assertEquals('OK', (string) $matchingResponse->getBody());
        self::assertEquals('KO', (string) $nonMatchingResponse->getBody());
    }

    /**
     * @return array<string, array{0: RouteGroup}>
     */
    public static function providerRouteGroupsWithCustomParameters(): array
    {
        return [
            'pattern defined in route group' => [
                new RouteGroup(
                    routes: [Route::get(path: '/{foo}', callback: static fn(): string => 'OK')],
                    patterns: ['foo' => '\d+'],
                )
            ],
            'route pattern takes precedence over route group pattern' => [
                new RouteGroup(
                    routes: [Route::get(path: '/{foo}', callback: static fn(): string => 'OK', patterns: ['foo' => '\d+'])],
                    patterns: ['foo' => '[a-z]+'],
                )
            ],
        ];
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
}
