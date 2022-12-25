<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum\DoctrineEnum\Type;

use Doctrine\DBAL\Types\Type;
use Enumeum\DoctrineEnum\Definition\Definition;

class TypeRegistryLoader
{
    /**
     * @param iterable<Definition> $definitions
     */
    public static function load(iterable $definitions): void
    {
        $typeRegistry = Type::getTypeRegistry();

        foreach ($definitions as $definition) {
            $name = $definition->name;
            if (!$typeRegistry->has($name)) {
                $typeRegistry->register($name, EnumeumType::create($name));
            }
        }
    }
}
