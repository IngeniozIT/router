<?php

declare(strict_types=1);

namespace IngeniozIT\Router;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\{ServerRequestInterface, ResponseInterface};
use Psr\Http\Server\{RequestHandlerInterface, MiddlewareInterface};
use Closure;

final readonly class Router implements RequestHandlerInterface
{
    public function __construct(
        private RouteGroup $routeGroup,
        private ContainerInterface $container,
        private ?Closure $fallback = null,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        foreach ($this->routeGroup->routes as $route) {
            if ($route->match($request) === false) {
                continue;
            }

            /** @var object $callback */
            $callback = is_string($route->callback) ?
                $this->container->get($route->callback) :
                $route->callback;
            if ($callback instanceof RequestHandlerInterface) {
                return $callback->handle($request);
            }

            if ($callback instanceof MiddlewareInterface) {
                return $callback->process($request, $this);
            }

            if (!is_callable($callback)) {
                throw new InvalidRoute('Route callback is not callable.');
            }

            return $callback($request, $this);
        }

        if (!$this->fallback instanceof \Closure) {
            throw new EmptyRouteStack('No routes left to process.');
        }

        return ($this->fallback)($request);
    }
}