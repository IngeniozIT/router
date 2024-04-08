<?php

declare(strict_types=1);

namespace IngeniozIT\Router\Route\Exception;

use UnexpectedValueException;

final class InvalidRouteParameter extends UnexpectedValueException
{
    public function __construct(
        public string $routeName,
        public string $parameterName,
        public string $expectedPattern,
    ) {
        parent::__construct("Parameter " . $parameterName . " for route " . $routeName . ' does not match the pattern ' . $expectedPattern . '.');
    }
}
