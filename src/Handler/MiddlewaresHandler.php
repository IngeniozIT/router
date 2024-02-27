<?php

declare(strict_types=1);

namespace IngeniozIT\Router\Handler;

use Closure;
use IngeniozIT\Router\Exception\InvalidRouteMiddleware;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

readonly final class MiddlewaresHandler
{
    private Closure $handler;

    public function __construct(
        private ContainerInterface $container,
        mixed $callback,
    ) {
        $handler = is_string($callback) ? $this->container->get($callback) : $callback;

        if (is_callable($handler)) {
            $this->handler = $handler(...);
            return;
        }

        if ($handler instanceof MiddlewareInterface) {
            $this->handler = $handler->process(...);
            return;
        }

        throw new InvalidRouteMiddleware('Middleware must be a PSR Middleware or a callable.');
    }

    public function handle(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $result = ($this->handler)($request, $handler);

        if (!$result instanceof ResponseInterface) {
            throw new InvalidRouteMiddleware('Middleware must return a PSR Response.');
        }

        return $result;
    }
}
