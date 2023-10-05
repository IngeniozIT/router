<?php

declare(strict_types=1);

namespace IngeniozIT\Router;

use Closure;
use IngeniozIT\Router\Tests\Fakes\TestMiddleware;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

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
            if ($route->matches($request) === false) {
                continue;
            }
            /** @var object $callback */
            $callback = is_string($route->callback) ?
                $this->container->get($route->callback) :
                $route->callback;
            if (is_a($callback, RequestHandlerInterface::class)) {
                return $callback->handle($request);
            }
            if (is_a($callback, TestMiddleware::class)) {
                return $callback->process($request, $this);
            }
            if (!is_callable($callback)) {
                throw new InvalidRoute('Route callback is not callable.');
            }
            return $callback($request, $this);
        }
        if (empty($this->fallback)) {
            throw new EmptyRouteStack('No routes left to process.');
        }
        return ($this->fallback)($request);
    }
}
