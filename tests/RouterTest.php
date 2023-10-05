<?php

declare(strict_types=1);

namespace IngeniozIT\Router\Tests;

use IngeniozIT\Http\Message\UriFactory;
use IngeniozIT\Router\EmptyRouteStack;
use IngeniozIT\Router\InvalidRoute;
use IngeniozIT\Router\Tests\Fakes\TestHandler;
use IngeniozIT\Router\Tests\Fakes\TestMiddleware;
use PHPUnit\Framework\TestCase;
use IngeniozIT\Router\Route;
use IngeniozIT\Router\Router;
use IngeniozIT\Router\RouteGroup;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Closure;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
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
            'RequestHandler callable' => [fn(ServerRequestInterface $request) => self::response('TEST')],
            'RequestHandler DI Container name' => [TestHandler::class],
            'Middleware' => [new TestMiddleware(self::responseFactory(), self::streamFactory())],
            'Middleware callable' => [fn(ServerRequestInterface $request, RequestHandlerInterface $handler) => self::response('TEST')],
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
            Route::get(path: '/test', callback: fn() => self::response('TEST')),
            Route::get(path: '/test2', callback: fn() => self::response('TEST2')),
        ]);
        $request = self::serverRequest('GET', '/test2');

        $response = $this->router($routeGroup)->handle($request);

        self::assertEquals('TEST2', (string) $response->getBody());
    }

    public function testFiltersOutRoutesWithWrongMethod(): void
    {
        $routeGroup = new RouteGroup(routes: [
            Route::get(path: '/', callback: fn() => self::response('TEST')),
            Route::post(path: '/', callback: fn() => self::response('TEST2')),
        ]);
        $request = self::serverRequest('POST', '/');

        $response = $this->router($routeGroup)->handle($request);

        self::assertEquals('TEST2', (string) $response->getBody());
    }

    public function testMustFindARouteToProcess(): void
    {
        $routeGroup = new RouteGroup(routes: [
            Route::get(path: '/foo', callback: fn() => self::response('TEST')),
            Route::get(path: '/bar', callback: fn() => self::response('TEST2')),
        ]);
        $request = self::serverRequest('GET', '/');

        self::expectException(EmptyRouteStack::class);
        $this->router($routeGroup)->handle($request);
    }

    public function testCanHaveAFallbackRoute(): void
    {
        $routeGroup = new RouteGroup(routes: []);
        $request = self::serverRequest('GET', '/');

        $response = $this->router($routeGroup, fn() => self::response('TEST'))->handle($request);

        self::assertEquals('TEST', (string) $response->getBody());
    }
}
