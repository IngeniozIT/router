<?php

namespace IngeniozIT\Router\Tests;

use IngeniozIT\Router\Exception\InvalidRouteParameter;
use IngeniozIT\Router\Exception\MissingRouteParameters;
use IngeniozIT\Router\Exception\RouteNotFound;
use IngeniozIT\Router\Route;
use IngeniozIT\Router\RouteGroup;
use IngeniozIT\Router\Tests\Utils\RouterCase;

final class NameTest extends RouterCase
{
    public function testRouterCanFindARoutePathByName(): void
    {
        $routeGroup = new RouteGroup([
            Route::get('/bar', 'foo', name: 'not_this_route'),
            Route::get('/foo', 'foo', name: 'route_name'),
        ]);
        $router = $this->router(new RouteGroup([
            new RouteGroup([]),
            $routeGroup
        ]));

        $result = $router->pathTo('route_name');

        self::assertSame('/foo', $result);
    }

    public function testRouterCanFindARouteWithParametersPathByName(): void
    {
        $routeGroup = new RouteGroup([
            Route::get('/{foo:\d+}', 'foo', name: 'route_name'),
        ]);
        $router = $this->router($routeGroup);

        $result = $router->pathTo('route_name', ['foo' => 42]);

        self::assertSame('/42', $result);
    }

    public function testAdditionalParametersAreAddedToThePathQuery(): void
    {
        $routeGroup = new RouteGroup([
            Route::get('/{foo:\d+}', 'foo', name: 'route_name'),
        ]);
        $router = $this->router($routeGroup);

        $result = $router->pathTo('route_name', ['foo' => '42', 'bar' => 'baz']);

        self::assertSame('/42?bar=baz', $result);
    }

    public function testRouteGroupsPassTheirNameToTheirSubRoutes(): void
    {
        $routeGroup = new RouteGroup(
            [
                Route::get('/foo', 'foo', name: 'route_name'),
            ],
            name: 'group',
        );
        $router = $this->router(new RouteGroup([$routeGroup]));

        $result = $router->pathTo('group.route_name');

        self::assertSame('/foo', $result);
    }

    public function testRouterCannotFindAnInexistingRoutePathByName(): void
    {
        $route = Route::get('/foo', 'foo', name: 'route_name');
        $router = $this->router(new RouteGroup([$route]));

        self::expectException(RouteNotFound::class);
        $router->pathTo('inexisting_route_name');
    }

    public function testRouterCannotFindARoutePathWithMissingParameters(): void
    {
        $route = Route::get('/{foo}/{bar}', 'foo', name: 'route_name');
        $router = $this->router(new RouteGroup([$route]));

        self::expectException(MissingRouteParameters::class);
        self::expectExceptionMessage("Missing parameters foo for route with name 'route_name'.");
        $router->pathTo('route_name', ['bar' => '42']);
    }

    public function testRouterCannotFindARoutePathWithInvalidParameters(): void
    {
        $route = Route::get('/{foo:\d+}', 'foo', name: 'route_name');
        $router = $this->router(new RouteGroup([$route]));

        self::expectException(InvalidRouteParameter::class);
        self::expectExceptionMessage("Parameter 'foo' for route with name 'route_name' does not match the pattern '\d+'.");
        $router->pathTo('route_name', ['foo' => 'bar']);
    }
}
