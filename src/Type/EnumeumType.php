<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine enumerations extension for Postgres" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
