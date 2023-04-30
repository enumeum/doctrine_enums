<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum\DoctrineEnum\Definition;

use Enumeum\DoctrineEnum\Attribute\EnumType;
use Enumeum\DoctrineEnum\Exception\MappingException;
use Enumeum\DoctrineEnum\Tools\EnumCasesExtractor;
use ReflectionEnum;
use ReflectionException;

class DefinitionRegistry
{
    /** @var array<string, Definition> */
    private array $definitionsByEnum = [];

    /** @var array<string, Definition> */
    private array $definitionsByName = [];

    /** @var array<string, class-string> */
    private array $enumsByName = [];

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

    public function getDefinition(string $name): ?Definition
    {
        return $this->definitionsByName[$name] ?? null;
    }

    /**
     * @return array<string, Definition>
     */
    public function getDefinitions(): array
    {
        return $this->definitionsByName;
    }

    /**
     * @param iterable<class-string> $enumClassNames
     *
     * @throws ReflectionException
     * @throws MappingException
     */
    public function load(iterable $enumClassNames): void
    {
        foreach ($enumClassNames as $enumClassName) {
            $this->loadType($enumClassName);
        }
    }

    /**
     * @throws ReflectionException
     * @throws MappingException
     */
    public function loadType(string $enumClassName): void
    {
        if (isset($this->definitionsByEnum[$enumClassName])) {
            return;
        }

        if ($created = $this->createDefinition($enumClassName)) {
            if (isset($this->enumsByName[$created->name])) {
                throw MappingException::typeWithSameNameAlreadyLoadedFromAnotherEnum($created->name, $enumClassName, $this->enumsByName[$created->name]);
            }

            $this->definitionsByEnum[$enumClassName] = $created;
            $this->definitionsByName[$created->name] = $created;
            $this->enumsByName[$created->name] = $enumClassName;
        }
    }

    /**
     * @throws ReflectionException
     * @throws MappingException
     */
    private function createDefinition(string $enumClassName): ?Definition
    {
        $reflection = new ReflectionEnum($enumClassName);
        if ($typeName = $this->tryMappedEnumName($reflection)) {
            return new Definition($typeName, EnumCasesExtractor::fromEnum($enumClassName));
        }

        return null;
    }

    private function tryMappedEnumName(ReflectionEnum $reflection): ?string
    {
        foreach ($reflection->getAttributes(EnumType::class) as $attribute) {
            return $attribute->getArguments()['name'] ?? null;
        }

        return null;
    }
}
