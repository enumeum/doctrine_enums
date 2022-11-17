<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum\DoctrineEnum;

use Enumeum\DoctrineEnum\Definition\DatabaseDefinitionRegistry;
use Enumeum\DoctrineEnum\Definition\DefinitionRegistry;
use Enumeum\DoctrineEnum\EnumUsage\TableUsageRegistry;
use Enumeum\DoctrineEnum\QueryBuild\ColumnDefaultQueryBuilder;
use Enumeum\DoctrineEnum\QueryBuild\EnumQueryBuilder;
use Enumeum\DoctrineEnum\QueryBuild\LockQueryBuilder;
use Enumeum\DoctrineEnum\Tools\EnumChangesTool;

use function array_push;
use function key_exists;

class DatabaseUpdateQueryBuilder
{
    public function __construct(
        private readonly DefinitionRegistry $registry,
        private readonly DatabaseDefinitionRegistry $databaseRegistry,
        private readonly TableUsageRegistry $usageRegistry,
    ) {
    }

    /**
     * @return iterable<string>
     */
    public function generateEnumCreateQueries(): iterable
    {
        $result = [];
        foreach ($this->collectCreateChangeSet() as $name) {
            array_push(
                $result,
                ...EnumQueryBuilder::buildEnumTypeCreateSql($this->registry->getDefinitionByName($name)),
            );
        }

        return $result;
    }

    /**
     * @return iterable<string>
     */
    public function generateEnumAlterQueries(): iterable
    {
        $result = [];
        foreach ($this->collectAlterChangeSet() as $name) {
            array_push(
                $result,
                ...EnumQueryBuilder::buildEnumTypeAlterSql(
                    $this->registry->getDefinitionByName($name),
                    $this->databaseRegistry->getDefinitionByName($name),
                ),
            );
        }

        return $result;
    }

    /**
     * @return iterable<string>
     */
    public function generateEnumReorderQueries(): iterable
    {
        $result = [];
        foreach ($this->collectReorderChangeSet() as $name) {
            $definition = $this->registry->getDefinitionByName($name);

            array_push($result, ...EnumQueryBuilder::buildEnumTypeRenameToTemporarySql($definition));
            array_push($result, ...EnumQueryBuilder::buildEnumTypeCreateSql($definition));

            if ($usage = $this->usageRegistry->getUsage($name)) {
                foreach ($usage->columns as $column) {
                    array_push($result, ...LockQueryBuilder::buildLockTableSql($column->table));
                    if ($column->default) {
                        array_push($result, ...ColumnDefaultQueryBuilder::buildDropColumnDefaultSql($column->table, $column->column));
                    }
                    array_push(
                        $result,
                        ...EnumQueryBuilder::buildEnumTypeAlterColumnSql($definition, $column->table, $column->column),
                    );
                    if ($column->default) {
                        array_push($result, ...ColumnDefaultQueryBuilder::buildSetColumnDefaultSql($column->table, $column->column, $column->default));
                    }
                }
            }

            array_push($result, ...EnumQueryBuilder::buildEnumTypeDropTemporarySql($definition));
        }

        return $result;
    }

    /**
     * @return iterable<string>
     */
    public function generateEnumDropQueries(): iterable
    {
        $result = [];
        foreach ($this->collectDropChangeSet() as $name) {
            array_push(
                $result,
                ...EnumQueryBuilder::buildEnumTypeDropSqlByDatabaseDefinition(
                    $this->databaseRegistry->getDefinitionByName($name),
                ),
            );
        }

        return $result;
    }

    /**
     * @return iterable<string>
     */
    private function collectCreateChangeSet(): iterable
    {
        $definitions = $this->registry->getDefinitionsHashedByName();
        $databaseDefinitions = $this->databaseRegistry->getDefinitionsHashedByName();

        $creating = [];
        foreach ($definitions as $name => $definition) {
            if (!key_exists($name, $databaseDefinitions)) {
                $creating[] = $name;
            }
        }

        return $creating;
    }

    /**
     * @return iterable<string>
     */
    private function collectAlterChangeSet(): array
    {
        $databaseDefinitions = $this->databaseRegistry->getDefinitionsHashedByName();
        $definitions = $this->registry->getDefinitionsHashedByName();

        $altering = [];
        foreach ($databaseDefinitions as $name => $databaseDefinition) {
            if (!key_exists($name, $definitions)) {
                continue;
            }
            if (EnumChangesTool::isChanged($databaseDefinition->cases, $definitions[$name]->cases)
                && !EnumChangesTool::isReorderingRequired($databaseDefinition->cases, $definitions[$name]->cases)
            ) {
                $altering[] = $name;
            }
        }

        return $altering;
    }

    /**
     * @return iterable<string>
     */
    private function collectReorderChangeSet(): array
    {
        $databaseDefinitions = $this->databaseRegistry->getDefinitionsHashedByName();
        $definitions = $this->registry->getDefinitionsHashedByName();

        $reordering = [];
        foreach ($databaseDefinitions as $name => $databaseDefinition) {
            if (!key_exists($name, $definitions)) {
                continue;
            }
            if (EnumChangesTool::isChanged($databaseDefinition->cases, $definitions[$name]->cases)
                && EnumChangesTool::isReorderingRequired($databaseDefinition->cases, $definitions[$name]->cases)
            ) {
                $reordering[] = $name;
            }
        }

        return $reordering;
    }

    /**
     * @return iterable<string>
     */
    private function collectDropChangeSet(): array
    {
        $databaseDefinitions = $this->databaseRegistry->getDefinitionsHashedByName();
        $definitions = $this->registry->getDefinitionsHashedByName();

        $dropping = [];
        foreach ($databaseDefinitions as $name => $databaseDefinition) {
            if (!key_exists($name, $definitions)) {
                $dropping[] = $name;
            }
        }

        return $dropping;
    }
}
