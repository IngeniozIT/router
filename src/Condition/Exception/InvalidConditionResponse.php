<?php

declare(strict_types=1);

namespace IngeniozIT\Router\Condition\Exception;

use IngeniozIT\Router\Condition\ConditionException;
use InvalidArgumentException;

use function get_debug_type;

final class InvalidConditionResponse extends InvalidArgumentException implements ConditionException
{
    public function __construct(public mixed $response)
    {
        parent::__construct(
            'Condition must either return an array or a boolean, ' . get_debug_type($response) . ' given.'
        );
    }
}
