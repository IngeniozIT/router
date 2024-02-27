<?php

namespace IngeniozIT\Router\Tests\Features;

use IngeniozIT\Router\Route;
use IngeniozIT\Router\RouteGroup;
use IngeniozIT\Router\Tests\RouterCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AdditionalAttributesTest extends RouterCase
{
    public function testAddsRouteAttributesToRequest(): void
    {
        $routeGroup = new RouteGroup(routes: [
            Route::get(
                path: '/',
                callback: static fn(ServerRequestInterface $request): ResponseInterface => self::response(
                    $request->getAttribute('foo')
                ),
                with: ['foo' => 'bar'],
            ),
        ]);
        $request = self::serverRequest('GET', '/');

        $response = $this->router($routeGroup)->handle($request);

        self::assertEquals('bar', (string)$response->getBody());
    }

    public function testAddsRouteGroupAttributesToRequest(): void
    {
        $routeGroup = new RouteGroup(
            routes: [
                Route::get(
                    path: '/',
                    callback: static fn(ServerRequestInterface $request): ResponseInterface => self::response(
                        $request->getAttribute('foo') . $request->getAttribute('bar')
                    ),
                    with: ['foo' => 'bar'],
                ),
            ],
            with: ['bar' => 'baz'],
        );
        $request = self::serverRequest('GET', '/');

        $response = $this->router($routeGroup)->handle($request);

        self::assertEquals('barbaz', (string)$response->getBody());
    }

    public function testRouteAttributesTakePrecedenceOverRouteGroupAttributes(): void
    {
        $routeGroup = new RouteGroup(
            routes: [
                Route::get(
                    path: '/',
                    callback: static fn(ServerRequestInterface $request): ResponseInterface => self::response(
                        $request->getAttribute('foo')
                    ),
                    with: ['foo' => 'bar'],
                ),
            ],
            with: ['foo' => 'baz'],
        );
        $request = self::serverRequest('GET', '/');

        $response = $this->router($routeGroup)->handle($request);

        self::assertEquals('bar', (string)$response->getBody());
    }

    public function testPathParametersTakePrecedenceOverRouteAttributes(): void
    {
        $routeGroup = new RouteGroup(routes: [
            Route::get(
                path: '/{foo}',
                callback: static fn(ServerRequestInterface $request): ResponseInterface => self::response(
                    $request->getAttribute('foo')
                ),
                with: ['foo' => 'baz'],
            ),
        ]);
        $request = self::serverRequest('GET', '/bar');

        $response = $this->router($routeGroup)->handle($request);

        self::assertEquals('bar', (string)$response->getBody());
    }
}
