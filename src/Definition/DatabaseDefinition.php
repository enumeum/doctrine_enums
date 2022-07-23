<?php declare(strict_types=1);

namespace Enumeum\DoctrineEnum\Definition;

class DatabaseDefinition
{
    public function __construct(
        public readonly string $name,
        public readonly iterable $cases,
    ) {
    }
}
