<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum\DoctrineEnum\QueryBuild;

use function sprintf;

class ColumnDefaultQueryBuilder
{
    private const DROP_COLUMN_DEFAULT_QUERY = 'ALTER TABLE %1$s ALTER COLUMN %2$s DROP DEFAULT';
    private const SET_COLUMN_DEFAULT_QUERY = 'ALTER TABLE %1$s ALTER COLUMN %2$s SET DEFAULT %3$s';

    public static function buildDropColumnDefaultSql(string $table, string $column): iterable
    {
        return [sprintf(self::DROP_COLUMN_DEFAULT_QUERY, $table, $column)];
    }

    public static function buildSetColumnDefaultSql(string $table, string $column, string $default): iterable
    {
        return [sprintf(self::SET_COLUMN_DEFAULT_QUERY, $table, $column, $default)];
    }
}
