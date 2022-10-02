<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine enumerations extension for Postgres" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum\DoctrineEnum\EnumUsage;

class TableUsageRegistry extends AbstractUsageRegistry
{
    private const TABLES_USING_TYPES_QUERY = <<<QUERY
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
    col.udt_name
;
QUERY;

    protected function getUsageQuery(): string
    {
        return self::TABLES_USING_TYPES_QUERY;
    }
}
