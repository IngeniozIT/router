<?php

declare(strict_types=1);

namespace IngeniozIT\Router\Exception;

use InvalidArgumentException;
use Throwable;

final class MissingRouteParameters extends InvalidArgumentException
{
    /**
     * @param string[] $missingParameters
     */
    public function __construct(
        string $routeName,
        array $missingParameters,
        ?Throwable $previous = null
    ) {
        parent::__construct(
            "Missing parameters " . implode(', ', $missingParameters) . " for route with name '$routeName'.",
            previous: $previous,
        );
    }
}
