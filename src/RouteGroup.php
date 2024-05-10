<?php

declare(strict_types=1);

namespace IngeniozIT\Router;

use IngeniozIT\Router\Route\RouteElement;

use function array_map;

final class RouteGroup
{
    /** @var RouteElement[]|RouteGroup[] */
    public array $routes;

    /**
     * @param array<RouteElement|RouteGroup> $routes
     * @param mixed[] $middlewares
     * @param mixed[] $conditions
     * @param array<string, string> $where
     * @param array<string, mixed> $with
     */
    public function __construct(
        array $routes,
        public array $middlewares = [],
        public array $conditions = [],
        public array $where = [],
        public array $with = [],
        public ?string $name = null,
        public ?string $path = null,
    ) {
        $this->routes = array_map($this->addRouteGroupInformationToRoute(...), $routes);
    }

    private function addRouteGroupInformationToRoute(RouteGroup|RouteElement $route): RouteGroup|RouteElement
    {
        return $route instanceof RouteGroup ?
            new RouteGroup(
                $route->routes,
                $route->middlewares,
                $route->conditions,
                $this->where,
                $this->with,
                $this->concatenatedNameForRouteGroup(),
                $this->path,
            ) :
            new RouteElement(
                $route->method,
                $this->path . $route->path,
                $route->callback,
                [...$this->where, ...$route->where],
                [...$this->with, ...$route->with],
                $this->concatenatedNameForRouteElement($route->name),
            );
    }

    private function concatenatedNameForRouteElement(?string $routeName): ?string
    {
        return $routeName === null ? null : $this->concatenatedNameForRouteGroup() . $routeName;
    }

    private function concatenatedNameForRouteGroup(): ?string
    {
        return $this->name === null ? null : str_replace('..', '.', $this->name . '.');
    }
}
