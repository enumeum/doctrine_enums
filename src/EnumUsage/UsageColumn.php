<?php declare(strict_types=1);

namespace Enumeum\DoctrineEnum\EnumUsage;

class UsageColumn
{
    public function __construct(
        public readonly string $name,
        public readonly string $table,
        public readonly string $column,
    ) {
    }
}
