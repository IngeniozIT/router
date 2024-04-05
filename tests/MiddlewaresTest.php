<?php

namespace IngeniozIT\Router\Tests;

use Exception;
use IngeniozIT\Http\Message\UriFactory;
use IngeniozIT\Router\Middleware\InvalidMiddlewareHandler;
use IngeniozIT\Router\Middleware\InvalidMiddlewareResponse;
use IngeniozIT\Router\Route;
use IngeniozIT\Router\RouteGroup;
use IngeniozIT\Router\Tests\Utils\RouterCase;
use IngeniozIT\Router\Tests\Utils\TestMiddleware;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

use function IngeniozIT\Edict\value;

final class MiddlewaresTest extends RouterCase
{
    #[DataProvider('providerMiddlewares')]
    public function testRouteGroupsCanHaveMiddlewares(mixed $middleware, string $expectedResponse): void
    {
        $routeGroup = new RouteGroup(
            routes: [
                Route::get(path: '/', callback: static fn(): ResponseInterface => self::response('TEST2')),
            ],
            middlewares: [$middleware],
        );
        $request = self::serverRequest('GET', '/');

        $response = $this->router($routeGroup)->handle($request);

        self::assertEquals($expectedResponse, (string)$response->getBody());
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
                'middleware' => static fn(
                    ServerRequestInterface $request,
                    RequestHandlerInterface $handler
                ): ResponseInterface => $handler->handle($request),
                'expectedResponse' => 'TEST2',
            ],
        ];
    }

    public function testRouteGroupsCanHaveMultipleMiddlewares(): void
    {
        $routeGroup = new RouteGroup(
            routes: [
                Route::get(path: '/', callback: static fn(): ResponseInterface => self::response('TEST2')),
            ],
            middlewares: [
                static fn(
                    ServerRequestInterface $request,
                    RequestHandlerInterface $handler
                ): ResponseInterface => $handler->handle($request),
                static fn(ServerRequestInterface $request, RequestHandlerInterface $handler) => throw new Exception(''),
            ],
        );
        $request = self::serverRequest('GET', '/');

        self::expectException(Exception::class);
        $response = $this->router($routeGroup)->handle($request);

        self::assertEquals('TEST', (string)$response->getBody());
    }

    /**
     * @param class-string<Throwable> $expectedException
     */
    #[DataProvider('providerInvalidMiddlewares')]
    public function testRouterCannotExecuteInvalidMiddlewares(
        mixed $middleware,
        string $expectedException,
        string $expectedMessage,
    ): void {
        $routeGroup = new RouteGroup(
            routes: [
                Route::get(path: '/', callback: static fn(): ResponseInterface => self::response('TEST')),
            ],
            middlewares: [$middleware],
        );
        $request = self::serverRequest('GET', '/');

        self::expectException($expectedException);
        self::expectExceptionMessage($expectedMessage);
        $this->router($routeGroup)->handle($request);
    }

    /**
     * @return array<string, array{mixed, class-string<Throwable>, string}>
     */
    public static function providerInvalidMiddlewares(): array
    {
        self::container()->set('not_a_callable', value('foo'));
        return [
            'not a middleware' => [
                UriFactory::class,
                InvalidMiddlewareHandler::class,
                'Middleware handler must be a PSR Middleware or a callable, IngeniozIT\Http\Message\UriFactory given.',
            ],
            'callable that does not return a response' => [
                static fn(): bool => true,
                InvalidMiddlewareResponse::class,
                'Middleware must return a PSR Response, bool given.',
            ],
        ];
    }
}
