<?php

declare(strict_types=1);

namespace IngeniozIT\Router\Middleware\Exception;

use IngeniozIT\Router\Middleware\MiddlewareException;
use InvalidArgumentException;

use function get_debug_type;

final class InvalidMiddlewareHandler extends InvalidArgumentException implements MiddlewareException
{
    public function __construct(public mixed $handler)
    {
        parent::__construct(
            'Middleware handler must be a PSR Middleware or a callable, ' . get_debug_type($handler) . ' given.'
        );
    }
}
