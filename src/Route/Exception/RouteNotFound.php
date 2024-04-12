<?php

declare(strict_types=1);

namespace IngeniozIT\Router\Route\Exception;

use DomainException;
use IngeniozIT\Router\Route\RouteException;

final class RouteNotFound extends DomainException implements RouteException
{
    public function __construct(public string $routeName)
    {
        parent::__construct('Route ' . $routeName . ' not found.');
    }
}
