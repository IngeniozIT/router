<?php

declare(strict_types=1);

namespace IngeniozIT\Router\Condition;

use InvalidArgumentException;

final class InvalidConditionResponse extends InvalidArgumentException implements ConditionException
{
    public function __construct(public mixed $response)
    {
        parent::__construct('Condition must either return an array or false, ' . get_debug_type($response) . ' given.');
    }
}
