<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum\DoctrineEnum\Schema;

use Enumeum\DoctrineEnum\Definition\Definition;
use Enumeum\DoctrineEnum\EnumUsage\Usage;

use function assert;

class DefinitionDiff
{
    public function __construct(
        public readonly Definition $fromDefinition,
        public readonly Definition $targetDefinition,
        public readonly ?Usage $usage = null,
    ) {
        assert($fromDefinition->name === $targetDefinition->name);
        if (null !== $usage) {
            assert($usage->name === $fromDefinition->name);
            assert($usage->name === $targetDefinition->name);
        }
    }
}
