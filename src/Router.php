<?php

declare(strict_types=1);

namespace IngeniozIT\Router;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\{ServerRequestInterface, ResponseInterface};
use Psr\Http\Server\{RequestHandlerInterface, MiddlewareInterface};

final readonly class Router implements RequestHandlerInterface
{
    public function __construct(
        private RouteGroup $routeGroup,
        private ContainerInterface $container,
        private mixed $fallback = null,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->routeGroup->conditions !== []) {
            return $this->executeConditions($request);
        }

        if ($this->routeGroup->middlewares !== []) {
            return $this->executeMiddlewares($request);
        }

        return $this->executeRoutes($request);
    }

    private function executeConditions(ServerRequestInterface $request): ResponseInterface
    {
        $newRequest = $request;
        while ($this->routeGroup->conditions !== []) {
            /** @var false|array<string, string> $matchedParams */
            $matchedParams = $this->executeCallback(array_shift($this->routeGroup->conditions), $newRequest);
            if ($matchedParams === false) {
                return $this->fallback($request);
            }

            foreach ($matchedParams as $key => $value) {
                $newRequest = $newRequest->withAttribute($key, $value);
            }
        }

        return $this->handle($newRequest);
    }

    private function executeMiddlewares(ServerRequestInterface $request): ResponseInterface
    {
        /** @var ResponseInterface */
        return $this->executeCallback(array_shift($this->routeGroup->middlewares), $request);
    }

    private function executeRoutes(ServerRequestInterface $request): ResponseInterface
    {
        foreach ($this->routeGroup->routes as $route) {
            $matchedParams = $route->match($request);
            if ($matchedParams === false) {
                continue;
            }

            $newRequest = $request;
            foreach ($matchedParams as $key => $value) {
                $newRequest = $newRequest->withAttribute($key, $value);
            }

            /** @var ResponseInterface */
            return $this->executeCallback($route->callback, $newRequest);
        }

        return $this->fallback($request);
    }

    private function fallback(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->fallback === null) {
            throw new EmptyRouteStack('No routes left to process.');
        }

        /** @var ResponseInterface */
        return $this->executeCallback($this->fallback, $request);
    }

    private function executeCallback(mixed $callback, ServerRequestInterface $request): mixed
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
