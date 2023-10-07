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
        if ($this->routeGroup->middlewares !== []) {
            return $this->executeCallback(array_shift($this->routeGroup->middlewares), $request);
        }

        foreach ($this->routeGroup->routes as $route) {
            $matchedParams = $route->match($request);
            if ($matchedParams === false) {
                continue;
            }

            $newRequest = $request;
            foreach ($matchedParams as $key => $value) {
                $newRequest = $newRequest->withAttribute($key, $value);
            }

            return $this->executeCallback($route->callback, $newRequest);
        }

        if (!$this->fallback instanceof \Closure) {
            throw new EmptyRouteStack('No routes left to process.');
        }

        return ($this->fallback)($request);
    }

    private function executeCallback(string|object $callback, ServerRequestInterface $request): ResponseInterface
    {
        if (is_string($callback)) {
            $callback = $this->container->get($callback);
        }

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
}
