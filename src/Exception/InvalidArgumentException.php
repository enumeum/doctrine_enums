<?php

declare(strict_types=1);

namespace Enumeum\DoctrineEnum\Exception;

use InvalidArgumentException as BaseInvalidArgumentException;

class InvalidArgumentException extends BaseInvalidArgumentException
{
    public static function enumReorderingIsProhibited(): self
    {
        return new self(
            'Enum should not be reordered with common Doctrine SchemaTool. Use Enumeum\DoctrineEnum\EnumTool for that.',
        );
    }
}
