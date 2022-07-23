<?php declare(strict_types=1);

namespace Enumeum\DoctrineEnum\Tools;

use BackedEnum;

class EnumCasesExtractor
{
    public static function fromEnum(string $enumClassName): iterable
    {
        assert(is_a($enumClassName, BackedEnum::class, true));

        return array_map(fn (BackedEnum $value) => $value->value, $enumClassName::cases());
    }
}
