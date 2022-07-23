<?php declare(strict_types=1);

namespace Enumeum\DoctrineEnum\EnumUsage;

class Usage
{
    /**
     * @param iterable<UsageColumn> $columns
     */
    public function __construct(
        public readonly string $name,
        public readonly iterable $columns,
    ) {
    }
}
