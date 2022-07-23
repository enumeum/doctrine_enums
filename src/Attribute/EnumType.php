<?php declare(strict_types=1);

namespace Enumeum\DoctrineEnum\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class EnumType
{
    public function __construct(
        public string $name
    ) {
    }
}
