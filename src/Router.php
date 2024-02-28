<?php

declare(strict_types=1);

namespace IngeniozIT\Router;

use IngeniozIT\Router\Exception\EmptyRouteStack;
use IngeniozIT\Router\Exception\RouteNotFound;
use IngeniozIT\Router\Handler\ConditionHandler;
use IngeniozIT\Router\Handler\MiddlewaresHandler;
use IngeniozIT\Router\Handler\RouteHandler;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;

final class Router implements RequestHandlerInterface
{
    private int $conditionIndex = 0;

    private int $middlewareIndex = 0;

    private int $routeIndex = 0;

    public function __construct(
        private readonly RouteGroup $routeGroup,
        private readonly ContainerInterface $container,
        private readonly mixed $fallback = null,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (isset($this->routeGroup->conditions[$this->conditionIndex])) {
            return $this->handleConditions($request);
        }

        if (isset($this->routeGroup->middlewares[$this->middlewareIndex])) {
            return $this->handleNextMiddleware($request);
        }

        return $this->handleRoutes($request);
    }

    private function handleConditions(ServerRequestInterface $request): ResponseInterface
    {
        $newRequest = $request;
        while (isset($this->routeGroup->conditions[$this->conditionIndex])) {
            $condition = new ConditionHandler($this->container, $this->routeGroup->conditions[$this->conditionIndex++]);

            $matchedParams = $condition->handle($newRequest);
            if ($matchedParams === false) {
                return $this->fallback($request);
            }

            foreach ($matchedParams as $key => $value) {
                $newRequest = $newRequest->withAttribute($key, $value);
            }
        }

        return $this->handle($newRequest);
    }

    private function handleNextMiddleware(ServerRequestInterface $request): ResponseInterface
    {
        $middlewaresHandler = new MiddlewaresHandler(
            $this->container,
            $this->routeGroup->middlewares[$this->middlewareIndex++]
        );

        return $middlewaresHandler->handle($request, $this);
    }

    private function handleRoutes(ServerRequestInterface $request): ResponseInterface
    {
        while (isset($this->routeGroup->routes[$this->routeIndex])) {
            $route = $this->routeGroup->routes[$this->routeIndex++];

            if ($route instanceof RouteGroup) {
                $newRouter = new Router(
                    $route,
                    $this->container,
                    $this->handle(...),
                );
                return $newRouter->handle($request);
            }

            $matchedParams = $route->match($request);
            if ($matchedParams === false) {
                continue;
            }

            return $this->handleRouteElement($request, $route, $matchedParams);
        }

        return $this->fallback($request);
    }

    /**
     * @param array<string, string> $matchedParams
     */
    private function handleRouteElement(
        ServerRequestInterface $request,
        RouteElement $route,
        array $matchedParams
    ): ResponseInterface {
        foreach ($route->with as $key => $value) {
            $request = $request->withAttribute($key, $value);
        }
        foreach ($matchedParams as $key => $value) {
            $request = $request->withAttribute($key, $value);
        }

        $routeHandler = new RouteHandler($this->container, $route->callback);
        return $routeHandler->handle($request, $this);
    }

    private function fallback(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->fallback === null) {
            throw new EmptyRouteStack('No routes left to process.');
        }

        $routeHandler = new RouteHandler($this->container, $this->fallback);
        return $routeHandler->handle($request, $this);
    }

    /**
     * @param array<string, scalar> $parameters
     */
    public function pathTo(string $routeName, array $parameters = []): string
    {
        $route = $this->findNamedRoute($routeName, $parameters, $this->routeGroup);

        if (!$route) {
            throw new RouteNotFound("Route with name '$routeName' not found.");
        }

        return $route;
    }

    /**
     * @param array<string, scalar> $parameters
     */
    private function findNamedRoute(string $routeName, array $parameters, RouteGroup $routeGroup): ?string
    {
        foreach ($routeGroup->routes as $route) {
            if ($route instanceof RouteGroup) {
                $foundRoute = $this->findNamedRoute($routeName, $parameters, $route);
                if ($foundRoute === null) {
                    continue;
                }
                return $foundRoute;
            }

            if ($route->name !== $routeName) {
                continue;
            }

            return $route->buildPath($parameters);
        }

        return null;
    }
}
