<?php

declare(strict_types=1);

namespace IngeniozIT\Router\Route;

use DomainException;

final class RouteNotFound extends DomainException implements RouteException
{
    public function __construct(public string $routeName)
    {
        parent::__construct('Route ' . $routeName . ' not found.');
    }
}
