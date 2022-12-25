<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum\DoctrineEnum\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;

use function sprintf;

class CastingEnumeumType extends EnumeumType
{
    private const CAST_EXPRESSION = '%1$s USING %2$s::text::%1$s';

    private ?string $castColumn = null;

    public function castColumn(string $column): self
    {
        $this->castColumn = $column;

        return $this;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return null === $this->castColumn
            ? $this->getName()
            : sprintf(self::CAST_EXPRESSION, $this->getName(), $this->castColumn)
        ;
    }
}
