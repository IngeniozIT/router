<?php

declare(strict_types=1);

namespace IngeniozIT\Router\Route\Exception;

use IngeniozIT\Router\Route\RouteException;
use InvalidArgumentException;

use function get_debug_type;

final class InvalidRouteHandler extends InvalidArgumentException implements RouteException
{
    public function __construct(public mixed $handler)
    {
        parent::__construct(
            'Route handler must be a PSR Middleware, a PSR RequestHandler or a callable, ' . get_debug_type(
                $handler
            ) . ' given.'
        );
    }
}
