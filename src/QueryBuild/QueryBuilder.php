<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum\DoctrineEnum\QueryBuild;

use Enumeum\DoctrineEnum\Definition\Definition;
use Enumeum\DoctrineEnum\Schema\DefinitionDiff;

use function array_push;
use function assert;

class QueryBuilder
{
    public static function create(): self
    {
        return new self();
    }

    /**
     * @param iterable<Definition> $definitions
     * @return iterable<string>
     */
    public function generateEnumCreateQueries(iterable $definitions): iterable
    {
        $result = [];
        foreach ($definitions as $definition) {
            assert($definition instanceof Definition);

            array_push(
                $result,
                ...EnumQueryBuilder::buildEnumTypeCreateSql($definition),
            );
        }

        return $result;
    }

    /**
     * @param iterable<DefinitionDiff> $diffs
     * @return iterable<string>
     */
    public function generateEnumAlterQueries(iterable $diffs): iterable
    {
        $result = [];
        foreach ($diffs as $diff) {
            assert($diff instanceof DefinitionDiff);
            array_push(
                $result,
                ...EnumQueryBuilder::buildEnumTypeAlterSql($diff->fromDefinition, $diff->targetDefinition),
            );
        }

        return $result;
    }

    /**
     * @param iterable<DefinitionDiff> $diffs
     * @return iterable<string>
     */
    public function generateEnumReorderQueries(iterable $diffs): iterable
    {
        $result = [];
        foreach ($diffs as $diff) {
            assert($diff instanceof DefinitionDiff);

            array_push($result, ...EnumQueryBuilder::buildEnumTypeRenameToTemporarySql($diff->targetDefinition));
            array_push($result, ...EnumQueryBuilder::buildEnumTypeCreateSql($diff->targetDefinition));

            if (null !== $diff->usage) {
                foreach ($diff->usage->columns as $column) {
                    array_push($result, ...LockQueryBuilder::buildLockTableSql($column->table));
                    if ($column->default) {
                        array_push($result, ...ColumnDefaultQueryBuilder::buildDropColumnDefaultSql($column->table, $column->column));
                    }
                    array_push(
                        $result,
                        ...EnumQueryBuilder::buildEnumTypeAlterColumnSql($diff->targetDefinition, $column->table, $column->column),
                    );
                    if ($column->default) {
                        array_push($result, ...ColumnDefaultQueryBuilder::buildSetColumnDefaultSql($column->table, $column->column, $column->default));
                    }
                }
            }

            array_push($result, ...EnumQueryBuilder::buildEnumTypeDropTemporarySql($diff->targetDefinition));
        }

        return $result;
    }

    /**
     * @param iterable<Definition> $definitions
     * @return iterable<string>
     */
    public function generateEnumDropQueries(iterable $definitions): iterable
    {
        $result = [];
        foreach ($definitions as $definition) {
            assert($definition instanceof Definition);

            array_push(
                $result,
                ...EnumQueryBuilder::buildEnumTypeDropSql($definition),
            );
        }

        return $result;
    }
}
