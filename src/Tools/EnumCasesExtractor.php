<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum\DoctrineEnum\Tools;

use BackedEnum;
use Enumeum\DoctrineEnum\Exception\MappingException;

use function array_map;
use function is_a;

class EnumCasesExtractor
{
    public static function fromEnum(string $enumClassName): iterable
    {
        if (is_a($enumClassName, BackedEnum::class, true)) {
            return array_map(static fn (BackedEnum $value) => (string) $value->value, $enumClassName::cases());
        }

        throw MappingException::typeMappedOnNonBackedEnumWhichNotSupported($enumClassName);
    }
}
