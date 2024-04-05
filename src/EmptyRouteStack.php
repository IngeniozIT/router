<?php

declare(strict_types=1);

namespace IngeniozIT\Router;

use OutOfRangeException;

final class EmptyRouteStack extends OutOfRangeException implements RouterException
{
    public function __construct()
    {
        parent::__construct('No routes left to process.');
    }
}
