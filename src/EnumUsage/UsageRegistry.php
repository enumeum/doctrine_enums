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

class UsageRegistry
{
    private const TYPES_QUERY = <<<QUERY
SELECT col.udt_name AS "name",
    col.table_name AS "table",
    col.column_name AS "column"
FROM information_schema.columns col
JOIN information_schema.tables tab ON tab.table_schema = col.table_schema
    AND tab.table_name = col.table_name
    AND tab.table_type = 'BASE TABLE'
JOIN pg_type typ ON col.udt_name = typ.typname
JOIN pg_enum enu ON typ.oid = enu.enumtypid
WHERE col.table_schema NOT IN ('information_schema', 'pg_catalog')
GROUP BY col.table_name,
    col.column_name,
    col.udt_name;
QUERY;

    private bool $loaded = false;

    /** @var Usage[] */
    private array $usagesByName;

    /** @var UsageColumn[][][] */
    private array $usagesByStructure;

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function getUsage(string $name): ?Usage
    {
        if (!$this->loaded) {
            $this->load();
        }

        return $this->usagesByName[$name] ?? null;
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
        $values = $this->connection->executeQuery(self::TYPES_QUERY)->fetchAllAssociative();
        foreach ($values as $value) {
            $this->usagesByStructure[$value['name']][$value['table']][$value['column']] =
                new UsageColumn($value['name'], $value['table'], $value['column']);
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
