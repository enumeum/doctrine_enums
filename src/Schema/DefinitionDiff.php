<?php

declare(strict_types=1);

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
