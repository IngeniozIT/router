<?php

namespace IngeniozIT\Router\Tests;

use Closure;
use IngeniozIT\Http\Message\UriFactory;
use IngeniozIT\Router\Route;
use IngeniozIT\Router\Route\InvalidRouteHandler;
use IngeniozIT\Router\Route\InvalidRouteResponse;
use IngeniozIT\Router\RouteGroup;
use IngeniozIT\Router\Tests\Utils\RouterCase;
use IngeniozIT\Router\Tests\Utils\TestHandler;
use IngeniozIT\Router\Tests\Utils\TestMiddleware;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final class CallbackTest extends RouterCase
{
    #[DataProvider('providerCallbacks')]
    public function testRouterCanExecuteACallback(Closure|MiddlewareInterface|RequestHandlerInterface|string $callback): void
    {
        $routeGroup = new RouteGroup(routes: [
            Route::get(path: '/', callback: $callback),
        ]);
        $request = self::serverRequest('GET', '/');

        $response = $this->router($routeGroup)->handle($request);

        self::assertEquals('TEST', (string)$response->getBody());
    }

    /**
     * @return array<string, array{0: Closure|MiddlewareInterface|RequestHandlerInterface|string}>
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function providerCallbacks(): array
    {
        return [
            'RequestHandler' => [new TestHandler(self::responseFactory(), self::streamFactory())],
            'RequestHandler callable' => [
                static fn(ServerRequestInterface $request): ResponseInterface => self::response('TEST')
            ],
            'RequestHandler DI Container name' => [TestHandler::class],
            'Middleware' => [new TestMiddleware(self::responseFactory(), self::streamFactory())],
            'Middleware callable' => [
                static fn(
                    ServerRequestInterface $request,
                    RequestHandlerInterface $handler
                ): ResponseInterface => self::response('TEST')
            ],
            'Middleware DI Container name' => [TestMiddleware::class],
        ];
    }

    /**
     * @param class-string<Throwable> $expectedException
     */
    #[DataProvider('providerInvalidHandlers')]
    public function testRouterCannotExecuteAnInvalidCallback(
        mixed $callback,
        string $expectedException,
        string $expectedMessage,
    ): void {
        $routeGroup = new RouteGroup(routes: [
            Route::get(path: '/', callback: $callback),
        ]);
        $request = self::serverRequest('GET', '/');

        self::expectException($expectedException);
        self::expectExceptionMessage($expectedMessage);
        $this->router($routeGroup)->handle($request);
    }

    /**
     * @return array<string, array{mixed, class-string<Throwable>, string}>
     */
    public static function providerInvalidHandlers(): array
    {
        return [
            'not a handler' => [
                UriFactory::class,
                InvalidRouteHandler::class,
                'Route handler must be a PSR Middleware, a PSR RequestHandler or a callable, IngeniozIT\Http\Message\UriFactory given.',
            ],
            'handler that does not return a PSR response' => [
                static fn(): array => ['foo' => 'bar'],
                InvalidRouteResponse::class,
                'Route must return a PSR Response, array given.',
            ],
        ];
    }
}
