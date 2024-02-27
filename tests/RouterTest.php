<?php

namespace IngeniozIT\Router\Tests;

use IngeniozIT\Router\Exception\EmptyRouteStack;
use IngeniozIT\Router\Route;
use IngeniozIT\Router\RouteGroup;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class RouterTest extends RouterCase
{
    public function testCanHandleAGroupOfRoutes(): void
    {
        $routeGroup = new RouteGroup(routes: [
            Route::get(path: '/foo', callback: static fn(): ResponseInterface => self::response('OK')),
        ]);
        $request = self::serverRequest('GET', '/foo');

        $response = $this->router($routeGroup)->handle($request);

        self::assertEquals('OK', (string)$response->getBody());
    }

    public function testRouteGroupCanHaveAPathPrefix(): void
    {
        $routeGroup = new RouteGroup(
            routes: [
                Route::get(path: '/bar', callback: static fn(): ResponseInterface => self::response('OK')),
            ],
            path: '/foo'
        );
        $request = self::serverRequest('GET', '/foo/bar');

        $response = $this->router($routeGroup)->handle($request);

        self::assertEquals('OK', (string)$response->getBody());
    }

    public function testFiltersOutNonMatchingPaths(): void
    {
        $routeGroup = new RouteGroup(routes: [
            Route::get(path: '/test2', callback: static fn(): ResponseInterface => self::response('KO')),
            Route::get(path: '/test', callback: static fn(): ResponseInterface => self::response('OK')),
        ]);
        $request = self::serverRequest('GET', '/test');

        $response = $this->router($routeGroup)->handle($request);

        self::assertEquals('OK', (string)$response->getBody());
    }

    public function testCanHaveSubGroups(): void
    {
        $routeGroup = new RouteGroup(
            routes: [
                new RouteGroup(
                    routes: [
                        Route::get(path: '/sub', callback: static fn(): ResponseInterface => self::response('TEST')),
                    ],
                ),
            ],
        );
        $request = self::serverRequest('GET', '/sub');

        $response = $this->router($routeGroup)->handle($request);

        self::assertEquals('TEST', (string)$response->getBody());
    }

    public function testCanHandleARouteAfterASubGroup(): void
    {
        $routeGroup = new RouteGroup(
            routes: [
                new RouteGroup(
                    routes: [
                        Route::get(path: '/sub', callback: static fn(): ResponseInterface => self::response('TEST')),
                    ],
                ),
                Route::get(path: '/after-sub', callback: static fn(): ResponseInterface => self::response('TEST2')),
            ],
        );
        $request = self::serverRequest('GET', '/after-sub');

        $response = $this->router($routeGroup)->handle($request);

        self::assertEquals('TEST2', (string)$response->getBody());
    }

    public function testCanUsePathParameters(): void
    {
        $routeGroup = new RouteGroup(routes: [
            Route::get(path: '/{foo}/{bar}', callback: static fn(ServerRequestInterface $request
            ): ResponseInterface => self::response($request->getAttribute('foo') . $request->getAttribute('bar'))),
        ]);
        $request = self::serverRequest('GET', '/bar/baz');

        $response = $this->router($routeGroup)->handle($request);

        self::assertEquals('barbaz', (string)$response->getBody());
    }

    /**
     * @dataProvider providerRouteGroupsWithCustomParameters
     */
    public function testCanUseCustomPathParameterPatterns(RouteGroup $routeGroup): void
    {
        $matchingRequest = self::serverRequest('GET', '/123');
        $nonMatchingRequest = self::serverRequest('GET', '/abc');

        $matchingResponse = $this->router($routeGroup)->handle($matchingRequest);
        $nonMatchingResponse = $this->router($routeGroup, static fn(): ResponseInterface => self::response('KO'))->handle($nonMatchingRequest);

        self::assertEquals('OK', (string)$matchingResponse->getBody());
        self::assertEquals('KO', (string)$nonMatchingResponse->getBody());
    }

    /**
     * @return array<string, array{0: RouteGroup}>
     */
    public static function providerRouteGroupsWithCustomParameters(): array
    {
        return [
            'pattern defined in path' => [
                new RouteGroup(
                    routes: [Route::get(path: '/{foo:\d+}', callback: static fn(): ResponseInterface => self::response('OK'))],
                )
            ],
            'pattern defined in route' => [
                new RouteGroup(
                    routes: [
                        Route::get(path: '/{foo}', callback: static fn(): ResponseInterface => self::response('OK'), where: ['foo' => '\d+'])
                    ],
                )
            ],
            'pattern defined in route group' => [
                new RouteGroup(
                    routes: [Route::get(path: '/{foo}', callback: static fn(): ResponseInterface => self::response('OK'))],
                    where: ['foo' => '\d+'],
                )
            ],
            'path pattern takes precedence over route pattern' => [
                new RouteGroup(
                    routes: [
                        Route::get(
                            path: '/{foo:\d+}',
                            callback: static fn(): ResponseInterface => self::response('OK'),
                            where: ['foo' => '[a-z]+']
                        )
                    ],
                )
            ],
            'route pattern takes precedence over route group pattern' => [
                new RouteGroup(
                    routes: [
                        Route::get(path: '/{foo}', callback: static fn(): ResponseInterface => self::response('OK'), where: ['foo' => '\d+'])
                    ],
                    where: ['foo' => '[a-z]+'],
                )
            ],
            'group pattern pattern takes precedence over containing route group pattern' => [
                new RouteGroup(
                    routes: [
                        new RouteGroup(
                            routes: [Route::get(path: '/{foo}', callback: static fn(): ResponseInterface => self::response('OK'))],
                            where: ['foo' => '\d+'],
                        )
                    ],
                    where: ['foo' => '[a-z]+'],
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
        $request = self::serverRequest('GET', '/baz');

        self::expectException(EmptyRouteStack::class);
        $this->router($routeGroup)->handle($request);
    }

    public function testCanHaveAFallbackRoute(): void
    {
        $routeGroup = new RouteGroup(routes: [
            Route::get(path: '/foo', callback: static fn(): ResponseInterface => self::response('TEST')),
            Route::get(path: '/bar', callback: static fn(): ResponseInterface => self::response('TEST2')),
        ]);
        $request = self::serverRequest('GET', '/');

        $response = $this->router(
            routeGroup: $routeGroup,
            fallback: static fn(): ResponseInterface => self::response('OK')
        )->handle($request);

        self::assertEquals('OK', (string) $response->getBody());
    }
}
