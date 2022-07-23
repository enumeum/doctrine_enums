<?php

declare(strict_types=1);

namespace Enumeum\DoctrineEnum\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class EnumeumType extends Type
{
    public const NAME = 'enumeum_enum';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return '';
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
