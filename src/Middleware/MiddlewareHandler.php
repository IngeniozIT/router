<?php

declare(strict_types=1);

namespace IngeniozIT\Router\Middleware;

use Closure;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

readonly final class MiddlewareHandler
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

        throw new InvalidMiddlewareHandler($handler);
    }

    public function handle(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $result = ($this->handler)($request, $handler);

        if (!$result instanceof ResponseInterface) {
            throw new InvalidMiddlewareResponse($result);
        }

        return $result;
    }
}
