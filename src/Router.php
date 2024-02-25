<?php

declare(strict_types=1);

namespace IngeniozIT\Router;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\{
    ServerRequestInterface,
    ResponseInterface,
    StreamFactoryInterface,
    ResponseFactoryInterface,
};
use Psr\Http\Server\{RequestHandlerInterface, MiddlewareInterface};

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
            return $this->executeMiddlewares($request);
        }

        return $this->executeRoutables($request);
    }

    private function executeConditions(ServerRequestInterface $request): ResponseInterface
    {
        $newRequest = $request;
        while (isset($this->routeGroup->conditions[$this->conditionIndex])) {
            $matchedParams = $this->executeCondition($this->routeGroup->conditions[$this->conditionIndex++], $newRequest);
            if ($matchedParams === false) {
                return $this->fallback($request);
            }

            foreach ($matchedParams as $key => $value) {
                $newRequest = $newRequest->withAttribute($key, $value);
            }
        }

        return $this->handle($newRequest);
    }

    /**
     * @return array<string, mixed>|false
     */
    private function executeCondition(mixed $callback, ServerRequestInterface $request): array|false
    {
        $handler = $this->resolveCallback($callback);

        if (!is_callable($handler)) {
            throw new InvalidRoute("Condition callback is not callable.");
        }

        $result = $handler($request);

        if ($result === false || is_array($result)) {
            return $result;
        }

        throw new InvalidRoute('Condition handler must return an array or false.');
    }

    private function executeMiddlewares(ServerRequestInterface $request): ResponseInterface
    {
        $middleware = $this->routeGroup->middlewares[$this->middlewareIndex++];
        $handler = $this->resolveCallback($middleware);

        if ($handler instanceof MiddlewareInterface) {
            return $handler->process($request, $this);
        }

        if (!is_callable($handler)) {
            throw new InvalidRoute("Middleware callback is not callable.");
        }

        return $this->processResponse($handler($request, $this));
    }

    private function executeRoutables(ServerRequestInterface $request): ResponseInterface
    {
        while (isset($this->routeGroup->routes[$this->routeIndex])) {
            $route = $this->routeGroup->routes[$this->routeIndex++];

            if ($route instanceof RouteGroup) {
                $newRouter = new Router($route, $this->container, $this->handle(...));
                return $newRouter->handle($request);
            }

            $matchedParams = $route->match($request, $this->routeGroup->patterns);
            if ($matchedParams === false) {
                continue;
            }

            $newRequest = $request;
            foreach ($matchedParams as $key => $value) {
                $newRequest = $newRequest->withAttribute($key, $value);
            }
            foreach ($route->with as $key => $value) {
                $newRequest = $newRequest->withAttribute($key, $value);
            }

            return $this->callRouteHandler($route->callback, $newRequest);
        }

        return $this->fallback($request);
    }

    private function callRouteHandler(mixed $callback, ServerRequestInterface $request): ResponseInterface
    {
        $handler = $this->resolveCallback($callback);

        if ($handler instanceof MiddlewareInterface) {
            return $handler->process($request, $this);
        }

        if ($handler instanceof RequestHandlerInterface) {
            return $handler->handle($request);
        }

        if (!is_callable($handler)) {
            throw new InvalidRoute("Route callback is not callable.");
        }

        return $this->processResponse($handler($request, $this));
    }

    private function fallback(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->fallback === null) {
            throw new EmptyRouteStack('No routes left to process.');
        }

        return $this->callRouteHandler($this->fallback, $request);
    }

    private function processResponse(mixed $response): ResponseInterface
    {
        if (is_string($response)) {
            /** @var StreamFactoryInterface $streamFactory */
            $streamFactory = $this->container->get(StreamFactoryInterface::class);
            /** @var ResponseFactoryInterface $responseFactory */
            $responseFactory = $this->container->get(ResponseFactoryInterface::class);
            $response = $responseFactory->createResponse()->withBody($streamFactory->createStream($response));
        }

        if (!$response instanceof ResponseInterface) {
            throw new InvalidRoute('Route callback did not return a valid response.');
        }

        return $response;
    }

    private function resolveCallback(mixed $callback): mixed
    {
        return is_string($callback) ? $this->container->get($callback) : $callback;
    }
}
