<?php

declare(strict_types=1);

namespace IngeniozIT\Router\Route;

use InvalidArgumentException;

final class InvalidRouteResponse extends InvalidArgumentException implements RouteException
{
    public function __construct(public mixed $response)
    {
        parent::__construct('Route must return a PSR Response, ' . get_debug_type($response) . ' given.');
    }
}
