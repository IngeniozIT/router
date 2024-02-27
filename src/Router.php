<?php

declare(strict_types=1);

namespace IngeniozIT\Router;

use IngeniozIT\Router\Exception\EmptyRouteStack;
use IngeniozIT\Router\Exception\InvalidRouteParameter;
use IngeniozIT\Router\Exception\MissingRouteParameter;
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
            return $this->executeConditions($request);
        }

        if (isset($this->routeGroup->middlewares[$this->middlewareIndex])) {
            $middlewaresHandler = new MiddlewaresHandler($this->container, $this->routeGroup->middlewares[$this->middlewareIndex++]);

            return $middlewaresHandler->handle($request, $this);
        }

        return $this->executeRoutables($request);
    }

    private function executeConditions(ServerRequestInterface $request): ResponseInterface
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

    private function executeRoutables(ServerRequestInterface $request): ResponseInterface
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

            $newRequest = $request;
            foreach ($route->with as $key => $value) {
                $newRequest = $newRequest->withAttribute($key, $value);
            }

            foreach ($matchedParams as $key => $value) {
                $newRequest = $newRequest->withAttribute($key, $value);
            }

            $routeHandler = new RouteHandler($this->container, $route->callback);
            return $routeHandler->handle($newRequest, $this);
        }

        return $this->fallback($request);
    }

    private function fallback(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->fallback === null) {
            throw new EmptyRouteStack('No routes left to process.');
        }

        $routeHandler = new RouteHandler($this->container, $this->fallback);
        return $routeHandler->handle($request, $this);
    }

    public function pathTo(string $routeName, array $parameters = []): string
    {
        $route = $this->findNamedRoute($routeName, $parameters, $this->routeGroup);

        if (!$route) {
            throw new RouteNotFound("Route with name '$routeName' not found.");
        }

        return $route;
    }

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

            if (!$route->hasParameters) {
                return $route->path;
            }

            $matchedParams = [];
            preg_match_all('#{(\w+)}#', $route->path, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                if (!isset($parameters[$match[1]])) {
                    throw new MissingRouteParameter($routeName, $match[1]);
                }
                if (!preg_match('#^' . $route->parameterPattern($match[1]) . '$#', $parameters[$match[1]])) {
                    throw new InvalidRouteParameter($routeName, $match[1], $route->parameterPattern($match[1]));
                }
                $matchedParams[$match[1]] = $parameters[$match[1]];
            }
            $path = $route->path;
            foreach ($matchedParams as $key => $value) {
                $path = str_replace('{' . $key . '}', $value, $path);
            }
            return $path;
        }

        return null;
    }
}
