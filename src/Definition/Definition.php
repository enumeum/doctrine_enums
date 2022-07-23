<?php declare(strict_types=1);

namespace Enumeum\DoctrineEnum\Definition;

use Enumeum\DoctrineEnum\Tools\EnumCasesExtractor;

class Definition
{
    public readonly iterable $cases;

    public function __construct(
        public readonly string $name,
        public readonly string $enumClassName,
        public readonly string $enumBackingType,
    ) {
        $this->cases = EnumCasesExtractor::fromEnum($enumClassName);
    }
}
