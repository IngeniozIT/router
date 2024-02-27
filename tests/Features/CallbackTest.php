<?php

namespace IngeniozIT\Router\Tests\Features;

use Closure;
use IngeniozIT\Http\Message\UriFactory;
use IngeniozIT\Router\InvalidRoute;
use IngeniozIT\Router\Route;
use IngeniozIT\Router\RouteGroup;
use IngeniozIT\Router\Tests\Fakes\TestHandler;
use IngeniozIT\Router\Tests\Fakes\TestMiddleware;
use IngeniozIT\Router\Tests\RouterCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CallbackTest extends RouterCase
{
    /**
     * @dataProvider providerCallbacks
     */
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
     * @dataProvider providerInvalidHandlers
     */
    public function testRouterCannotExecuteAnInvalidCallback(): void
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
}
