<?php

declare(strict_types=1);

namespace IngeniozIT\Router\Handler;

use Closure;
use IngeniozIT\Router\Exception\InvalidRouteCondition;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

readonly final class ConditionHandler
{
    private Closure $handler;

    public function __construct(
        private ContainerInterface $container,
        mixed $callback,
    ) {
        $handler = is_string($callback) ? $this->container->get($callback) : $callback;

        if (!is_callable($handler)) {
            throw new InvalidRouteCondition('Invalid condition handler');
        }

        $this->handler = $handler(...);
    }

    /**
     * @return array<string, mixed>|false
     */
    public function handle(ServerRequestInterface $request): array|false
    {
        $result = ($this->handler)($request);

        if ($result === false || is_array($result)) {
            return $result;
        }

        throw new InvalidRouteCondition('Condition must either return an array or false.');
    }
}
