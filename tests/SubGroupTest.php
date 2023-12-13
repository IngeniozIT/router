<?php

declare(strict_types=1);

namespace IngeniozIT\Router\Tests;

use PHPUnit\Framework\TestCase;
use IngeniozIT\Router\{Router, RouteGroup, Route};
use Closure;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class SubGroupTest extends TestCase
{
    use PsrTrait;

    private function router(RouteGroup $routeGroup, ?Closure $fallback = null): Router
    {
        return new Router($routeGroup, self::container(), $fallback);
    }

    public function testCanHaveSubGroups(): void
    {
        $routeGroup = new RouteGroup(
            routes: [
                new RouteGroup(
                    routes: [
                        Route::get(path: '/sub', callback: static fn() => 'TEST'),
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
                        Route::get(path: '/sub', callback: static fn() => 'TEST'),
                    ],
                ),
                Route::get(path: '/after-sub', callback: static fn() => 'TEST2'),
            ],
        );
        $request = self::serverRequest('GET', '/after-sub');

        $response = $this->router($routeGroup)->handle($request);

        self::assertEquals('TEST2', (string)$response->getBody());
    }
}
