<?php

declare(strict_types=1);

namespace IngeniozIT\Router\Exception;

use InvalidArgumentException;
use Throwable;

final class InvalidRouteParameter extends InvalidArgumentException
{
    public function __construct(
        string $routeName,
        string $missingParameters,
        string $pattern,
        ?Throwable $previous = null
    ) {
        parent::__construct(
            "Parameter '$missingParameters' for route with name '$routeName' does not match the pattern '$pattern'.",
            previous: $previous,
        );
    }
}
