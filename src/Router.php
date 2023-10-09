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
            $matchedParams = $this->callConditionHandler(array_shift($this->routeGroup->conditions), $newRequest);
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
    private function callConditionHandler(mixed $callback, ServerRequestInterface $request): array|false
    {
        $handler = $this->resolveCallback($callback);

        if (!is_callable($handler)) {
            throw new InvalidRoute('Condition handler cannot be called.');
        }

        $result = $handler($request);

        if ($result === false || is_array($result)) {
            return $result;
        }

        throw new InvalidRoute('Condition handler must return an array or false.');
    }

    private function executeMiddlewares(ServerRequestInterface $request): ResponseInterface
    {
        return $this->callMiddlewareHandler(array_shift($this->routeGroup->middlewares), $request);
    }

    private function callMiddlewareHandler(mixed $callback, ServerRequestInterface $request): ResponseInterface
    {
        $handler = $this->resolveCallback($callback);

        if ($handler instanceof MiddlewareInterface) {
            return $handler->process($request, $this);
        }

        if (!is_callable($handler)) {
            throw new InvalidRoute('Middleware handler cannot be called.');
        }

        return $this->processResponse($handler($request, $this));
    }

    private function executeRoutes(ServerRequestInterface $request): ResponseInterface
    {
        foreach ($this->routeGroup->routes as $route) {
            $matchedParams = $route->match($request, $this->routeGroup->patterns);
            if ($matchedParams === false) {
                continue;
            }

            $newRequest = $request;
            foreach ($matchedParams as $key => $value) {
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
            throw new InvalidRoute('Route handler cannot be called.');
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
