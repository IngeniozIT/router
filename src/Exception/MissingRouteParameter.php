<?php

declare(strict_types=1);

namespace IngeniozIT\Router\Exception;

use InvalidArgumentException;
use Throwable;

final class MissingRouteParameter extends InvalidArgumentException
{
    public function __construct(
        string $routeName,
        string $parameterName,
        ?Throwable $previous = null
    ) {
        parent::__construct(
            "Missing parameter '$parameterName' for route with name '$routeName'.",
            previous: $previous,
        );
    }
}
