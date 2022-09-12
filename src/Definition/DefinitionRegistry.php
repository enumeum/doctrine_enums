<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine enumerations extension for Postgres" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum\DoctrineEnum\Definition;

use Enumeum\DoctrineEnum\Attribute\EnumType;
use Enumeum\DoctrineEnum\Exception\UnexpectedValueException;
use ReflectionEnum;
use Throwable;

class DefinitionRegistry
{
    /** @var Definition[] */
    private array $definitionsByEnum = [];

    /** @var Definition[] */
    private array $definitionsByType = [];

    public function __construct(iterable $enumClassNames = [])
    {
        foreach ($enumClassNames as $enumClassName) {
            $this->loadType($enumClassName);
        }
    }

    public function getDefinitionByEnum(string $enumClassName): ?Definition
    {
        $this->loadType($enumClassName);

        return $this->definitionsByEnum[$enumClassName] ?? null;
    }

    public function getDefinitionByType(string $typeName): ?Definition
    {
        return $this->definitionsByType[$typeName] ?? null;
    }

    public function loadType(string $enumClassName): void
    {
        if (isset($this->definitionsByEnum[$enumClassName])) {
            return;
        }

        if ($created = $this->createDefinition($enumClassName)) {
            $this->definitionsByEnum[$enumClassName] = $created;
            $this->definitionsByType[$created->name] = $created;
        }
    }

    private function createDefinition(string $enumClassName): ?Definition
    {
        try {
            $reflection = new ReflectionEnum($enumClassName);
            if ($typeName = $this->tryMappedEnumName($reflection)) {
                return new Definition($typeName, $enumClassName, (string) $reflection->getBackingType());
            }
        } catch (Throwable) {
        }

        throw UnexpectedValueException::enumIsNotRelatedToBeEnumeumType($enumClassName);
    }

    private function tryMappedEnumName(ReflectionEnum $reflection): ?string
    {
        foreach ($reflection->getAttributes(EnumType::class) as $attribute) {
            return $attribute->getArguments()['name'] ?? null;
        }

        return null;
    }
}
