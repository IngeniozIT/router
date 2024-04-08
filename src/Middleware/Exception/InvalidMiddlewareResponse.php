<?php

declare(strict_types=1);

namespace IngeniozIT\Router\Middleware\Exception;

use IngeniozIT\Router\Middleware\MiddlewareException;
use InvalidArgumentException;

use function get_debug_type;

final class InvalidMiddlewareResponse extends InvalidArgumentException implements MiddlewareException
{
    public function __construct(public mixed $response)
    {
        parent::__construct('Middleware must return a PSR Response, ' . get_debug_type($response) . ' given.');
    }
}
