<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine enumerations extension for Postgres" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
