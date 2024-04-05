<?php

declare(strict_types=1);

namespace IngeniozIT\Router\Route;

use Closure;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

readonly final class RouteHandler
{
    private Closure|MiddlewareInterface|RequestHandlerInterface $handler;

    public function __construct(
        private ContainerInterface $container,
        mixed $callback,
    ) {
        $handler = is_string($callback) ? $this->container->get($callback) : $callback;

        if (
            !($handler instanceof MiddlewareInterface)
            && !($handler instanceof RequestHandlerInterface)
            && !is_callable($handler)
        ) {
            throw new InvalidRouteHandler($handler);
        }

        $this->handler = is_callable($handler) ? $handler(...) : $handler;
    }

    public function handle(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $result = $this->executeHandler($request, $handler);

        if (!$result instanceof ResponseInterface) {
            throw new InvalidRouteResponse($result);
        }

        return $result;
    }

    private function executeHandler(ServerRequestInterface $request, RequestHandlerInterface $handler): mixed
    {
        if ($this->handler instanceof RequestHandlerInterface) {
            return $this->handler->handle($request);
        }

        if ($this->handler instanceof MiddlewareInterface) {
            return $this->handler->process($request, $handler);
        }

        return ($this->handler)($request, $handler);
    }
}
