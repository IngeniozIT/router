<?php

declare(strict_types=1);

namespace IngeniozIT\Router\Route;

use DomainException;

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
