<?php

declare(strict_types=1);

namespace IngeniozIT\Router\Middleware;

use InvalidArgumentException;

final class InvalidMiddlewareHandler extends InvalidArgumentException implements MiddlewareException
{
    public function __construct(public mixed $handler)
    {
        parent::__construct('Middleware handler must be a PSR Middleware or a callable, ' . get_debug_type($handler) . ' given.');
    }
}
