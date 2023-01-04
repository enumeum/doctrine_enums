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
use Enumeum\DoctrineEnum\QueryBuild\QueryBuilder;

class Schema
{
    /** @var array<string, Definition> */
    private array $definitions = [];

    /** @var array<string, Usage> */
    private array $usages = [];

    /**
     * @param iterable<Definition> $definitions
     * @param iterable<Usage>      $usages
     */
    public function __construct(
        iterable $definitions = [],
        iterable $usages = [],
    ) {
        foreach ($definitions as $definition) {
            $this->addDefinition($definition);
        }

        foreach ($usages as $usage) {
            $this->addUsage($usage);
        }
    }

    public function __clone()
    {
        foreach ($this->usages as $k => $usage) {
            $this->usages[$k] = clone $usage;
        }

        foreach ($this->definitions as $k => $definition) {
            $this->definitions[$k] = clone $definition;
        }
    }

    public function hasDefinition(string $name): bool
    {
        return isset($this->definitions[$name]);
    }

    public function getDefinition(string $name): ?Definition
    {
        return $this->definitions[$name] ?? null;
    }

    /**
     * @return array<string, Definition>
     */
    public function getDefinitions(): array
    {
        return $this->definitions;
    }

    public function hasUsage(string $name): bool
    {
        return isset($this->usages[$name]);
    }

    public function getUsage(string $name): ?Usage
    {
        return $this->usages[$name] ?? null;
    }

    /**
     * @return array<string, Usage>
     */
    public function getUsages(): array
    {
        return $this->usages;
    }

    /**
     * @return iterable<string>
     */
    public function toSql(): iterable
    {
        return QueryBuilder::create()->generateEnumCreateQueries($this->definitions);
    }

    /**
     * @return iterable<string>
     */
    public function toDropSql(): iterable
    {
        return QueryBuilder::create()->generateEnumDropQueries($this->definitions);
    }

    public function addUsage(Usage $usage): void
    {
        $this->usages[$usage->name] = $usage;
    }

    public function addDefinition(Definition $definition): void
    {
        $this->definitions[$definition->name] = $definition;
    }
}
