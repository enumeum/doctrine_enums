<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine enumerations extension for Postgres" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum\DoctrineEnum\EnumUsage;

use Doctrine\DBAL\Connection;

abstract class AbstractUsageRegistry
{
    private bool $loaded = false;

    /** @var Usage[] */
    private array $usagesByName;

    /** @var UsageColumn[][][] */
    private array $usagesByStructure;

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    abstract protected function getUsageQuery(): string;

    public function getUsage(string $name): ?Usage
    {
        if (!$this->loaded) {
            $this->load();
        }

        return $this->usagesByName[$name] ?? null;
    }

    /**
     * @return iterable<Usage>
     */
    public function getUsagesHashedByName(): iterable
    {
        if (!$this->loaded) {
            $this->load();
        }

        return $this->usagesByName;
    }

    public function isUsedElsewhereExcept(string $name, string $table, string $column): bool
    {
        if (!$this->loaded) {
            $this->load();
        }

        if (null === $this->getUsage($name)) {
            return false;
        }

        foreach ($this->usagesByStructure[$name] as $usageTable) {
            foreach ($usageTable as $usageColumn) {
                if ($usageColumn->table !== $table || $usageColumn->column !== $column) {
                    return true;
                }
            }
        }

        return false;
    }

    private function load(): void
    {
        $values = $this->connection->executeQuery($this->getUsageQuery())->fetchAllAssociative();
        foreach ($values as $value) {
            $this->usagesByStructure[$value['name']][$value['table']][$value['column']] =
                new UsageColumn($value['name'], $value['table'], $value['column'], $value['default']);
        }

        foreach ($this->usagesByStructure as $name => $tables) {
            $usageColumns = [];
            foreach ($tables as $table) {
                foreach ($table as $column) {
                    $usageColumns[] = $column;
                }
            }
            $this->usagesByName[$name] = new Usage($name, $usageColumns);
        }

        $this->loaded = true;
    }
}
