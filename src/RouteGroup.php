<?php

declare(strict_types=1);

namespace IngeniozIT\Router;

final class RouteGroup
{
    /** @var Route[]|RouteGroup[] */
    public array $routes;

    /**
     * @param array<Route|RouteGroup> $routes
     * @param mixed[] $middlewares
     * @param mixed[] $conditions
     * @param array<string, string> $where
     * @param array<string, string> $with
     */
    public function __construct(
        array $routes,
        public array $middlewares = [],
        public array $conditions = [],
        array $where = [],
        array $with = [],
        ?string $name = null,
        ?string $path = null,
    ) {
        $this->routes = array_map(
            function (RouteGroup|Route $route) use ($with, $where, $name, $path): RouteGroup|Route {
                if ($route instanceof RouteGroup) {
                    return new RouteGroup(
                        $route->routes,
                        $route->middlewares,
                        $route->conditions,
                        $where,
                        $with,
                        $this->concatenatedName($name),
                        $path,
                    );
                }

                return new Route(
                    $route->method,
                    $path . $route->path,
                    $route->callback,
                    [...$where, ...$route->where],
                    [...$with, ...$route->with],
                    name: !empty($route->name) ? $this->concatenatedName($name) . $route->name : null,
                );
            },
            $routes,
        );
    }

    private function concatenatedName(?string $name): ?string
    {
        return $name === null || $name === '' ? $name : $name . '.';
    }
}
