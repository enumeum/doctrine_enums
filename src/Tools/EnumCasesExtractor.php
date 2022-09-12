<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine enumerations extension for Postgres" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum\DoctrineEnum\Tools;

use BackedEnum;

class EnumCasesExtractor
{
    public static function fromEnum(string $enumClassName): iterable
    {
        assert(is_a($enumClassName, BackedEnum::class, true));

        return array_map(static fn (BackedEnum $value) => $value->value, $enumClassName::cases());
    }
}
