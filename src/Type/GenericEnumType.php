<?php

declare(strict_types=1);

namespace Enumeum\DoctrineEnum\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class GenericEnumType extends Type
{
    private readonly string $name;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public static function create(string $name): self
    {
        $type = new self();
        $type->setName($name);

        return $type;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $this->name;
    }

//    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): int|string|null
//    {
//        if (null === $value) {
//            return null;
//        }
//
//        if (!$value instanceof BackedEnum) {
//            throw new InvalidArgumentException(
//                sprintf('Expected instance of BackedEnum, got `%s`.', \get_debug_type($value))
//            );
//        }
//
//        return $value->value;
//    }
//
//    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?BackedEnum
//    {
//        if (null === $value) {
//            return null;
//        }
//
//        /** @var  $enumClassName */
//        $enumClassName = $this->getEnumClassName();
//        assert(is_a($enumClassName, BackedEnum::class, true));
//
//        return $enumClassName::from($value);
//    }
//
//    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
//    {
//        return true;
//    }
}
