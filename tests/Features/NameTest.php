<?php

namespace IngeniozIT\Router\Tests\Features;

use IngeniozIT\Router\Exception\RouteNotFound;
use IngeniozIT\Router\Route;
use IngeniozIT\Router\RouteGroup;
use IngeniozIT\Router\Tests\RouterCase;

final class NameTest extends RouterCase
{
    public function testRouterCanFindARoutePathByName(): void
    {
        $routeGroup = new RouteGroup([
            Route::get('/foo', 'foo', name: 'route_name'),
        ]);
        $router = $this->router(new RouteGroup([
            new RouteGroup([]),
            $routeGroup
        ]));

        $result = $router->pathTo('route_name');

        self::assertSame('/foo', $result);
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
}
