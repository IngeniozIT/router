<?php

declare(strict_types=1);

namespace IngeniozIT\Router;

final class RouteGroup
{
    /**
     * @param array<Route|RouteGroup> $routes
     * @param mixed[] $middlewares
     * @param mixed[] $conditions
     * @param array<string, string> $patterns
     */
    public function __construct(
        public array $routes,
        public array $middlewares = [],
        public array $conditions = [],
        public array $patterns = [],
    ) {
    }
}
