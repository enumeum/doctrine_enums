<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine enumerations extension for Postgres" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum\DoctrineEnum\QueryBuild;

use Enumeum\DoctrineEnum\Definition\DatabaseDefinition;
use Enumeum\DoctrineEnum\Definition\Definition;
use Enumeum\DoctrineEnum\Tools\EnumChangesTool;

use function implode;
use function sprintf;

class EnumQueryBuilder
{
    private const TEMPORARY_SUFFIX = '__';

    private const TYPE_CREATE_QUERY = "CREATE TYPE %1\$s AS ENUM ('%2\$s')";
    private const TYPE_ALTER_QUERY = "ALTER TYPE %1\$s ADD VALUE IF NOT EXISTS '%2\$s'";
    private const TYPE_ALTER_RENAME_QUERY = 'ALTER TYPE %1$s RENAME TO %2$s';
    private const TYPE_ALTER_TABLE_QUERY = 'ALTER TABLE %2$s ALTER COLUMN %3$s TYPE %1$s USING %3$s::text::%1$s';
    private const TYPE_DROP_QUERY = 'DROP TYPE %1$s';

    public static function buildEnumTypeCreateSql(Definition $definition): iterable
    {
        return [sprintf(self::TYPE_CREATE_QUERY, $definition->name, implode("', '", [...$definition->cases]))];
    }

    public static function buildEnumTypeAlterSql(Definition $definition, DatabaseDefinition $databaseDefinition): iterable
    {
        $sql = [];
        $add = EnumChangesTool::resolveAddingValues($databaseDefinition->cases, $definition->cases);
        foreach ($add as $value) {
            $sql[] = sprintf(
                self::TYPE_ALTER_QUERY,
                $definition->name,
                $value,
            );
        }

        return $sql;
    }

    public static function buildEnumTypeDropSql(Definition $definition): iterable
    {
        return [sprintf(self::TYPE_DROP_QUERY, $definition->name)];
    }

    public static function buildEnumTypeRenameToTemporarySql(Definition $definition): iterable
    {
        return [sprintf(self::TYPE_ALTER_RENAME_QUERY, $definition->name, $definition->name.self::TEMPORARY_SUFFIX)];
    }

    public static function buildEnumTypeDropTemporarySql(Definition $definition): iterable
    {
        return [sprintf(self::TYPE_DROP_QUERY, $definition->name.self::TEMPORARY_SUFFIX)];
    }

    public static function buildEnumTypeAlterColumnSql(Definition $definition, string $table, string $column): iterable
    {
        return [sprintf(self::TYPE_ALTER_TABLE_QUERY, $definition->name, $table, $column)];
    }

    public static function buildEnumTypeDropSqlByDatabaseDefinition(DatabaseDefinition $definition): iterable
    {
        return [sprintf(self::TYPE_DROP_QUERY, $definition->name)];
    }
}
