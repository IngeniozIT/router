<?php

declare(strict_types=1);

namespace IngeniozIT\Router\Tests;

use Exception;
use PHPUnit\Framework\TestCase;
use IngeniozIT\Router\{
    Router,
    RouteGroup,
    Route,
    InvalidRoute,
};
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use IngeniozIT\Router\Tests\Fakes\TestMiddleware;
use Psr\Http\Server\RequestHandlerInterface;
use IngeniozIT\Http\Message\UriFactory;
use Closure;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
final class MiddlewareTest extends TestCase
{
    use PsrTrait;

    private function router(RouteGroup $routeGroup, ?Closure $fallback = null): Router
    {
        return new Router($routeGroup, self::container(), self::responseFactory(), self::streamFactory(), $fallback);
    }

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
            'middleware that returns a string' => [
                'middleware' => static fn(): string => 'TEST',
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

        self::expectException(InvalidRoute::class);
        $this->router($routeGroup)->handle($request);
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function providerInvalidMiddlewares(): array
    {
        return [
            'not a middleware' => [UriFactory::class],
            'value that cannot be converted to a response' => [static fn(): array => ['foo' => 'bar']],
        ];
    }
}
