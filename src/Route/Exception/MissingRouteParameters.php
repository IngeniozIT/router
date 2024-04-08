<?php

declare(strict_types=1);

namespace IngeniozIT\Router\Route\Exception;

use DomainException;
use IngeniozIT\Router\Route\RouteException;

use function implode;

final class MissingRouteParameters extends DomainException implements RouteException
{
    /**
     * @param string[] $parameters
     */
    public function __construct(
        public string $routeName,
        public array $parameters,
    ) {
        parent::__construct('Missing parameters ' . implode(', ', $parameters) . ' for route ' . $routeName . '.');
    }
}
