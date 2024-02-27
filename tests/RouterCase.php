<?php

namespace IngeniozIT\Router\Tests;

use Closure;
use IngeniozIT\Router\RouteGroup;
use IngeniozIT\Router\Router;
use PHPUnit\Framework\TestCase;

class RouterCase extends TestCase
{
    use PsrTrait;

    protected function router(RouteGroup $routeGroup, ?Closure $fallback = null): Router
    {
        return new Router($routeGroup, self::container(), $fallback);
    }
}
