<?php

declare(strict_types=1);

namespace IngeniozIT\Router;

use Psr\Http\Server\MiddlewareInterface;
use Closure;

final class RouteGroup
{
    /**
     * @param Route[] $routes
     * @param array<Closure|MiddlewareInterface|string> $middlewares
     * @param array<string, string> $patterns
     */
    public function __construct(
        public array $routes,
        public array $middlewares = [],
        public array $patterns = [],
    ) {
    }
}
