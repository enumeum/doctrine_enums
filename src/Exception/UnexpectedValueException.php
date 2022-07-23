<?php

declare(strict_types=1);

namespace Enumeum\DoctrineEnum\Exception;

use UnexpectedValueException as BaseUnexpectedValueException;
use function sprintf;

class UnexpectedValueException extends BaseUnexpectedValueException
{
    public static function enumIsNotRelatedToBeEnumeumType(string $type): self
    {
        return new self(sprintf('Passed type "%s" is not related to be of EnumeumType', $type));
    }
}
