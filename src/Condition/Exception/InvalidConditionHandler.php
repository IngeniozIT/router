<?php

declare(strict_types=1);

namespace IngeniozIT\Router\Condition\Exception;

use IngeniozIT\Router\Condition\ConditionException;
use InvalidArgumentException;

use function get_debug_type;

final class InvalidConditionHandler extends InvalidArgumentException implements ConditionException
{
    public function __construct(public mixed $handler)
    {
        parent::__construct('Condition handler must be a callable, ' . get_debug_type($handler) . ' given.');
    }
}
