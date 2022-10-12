<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine enumerations extension for Postgres" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum\DoctrineEnum\EnumUsage;

class MaterialViewUsageRegistry extends AbstractUsageRegistry
{
    private const MATERIAL_VIEWS_USING_TYPES_QUERY = <<<QUERY
SELECT DISTINCT
    t.typname AS "name",
    c.relname as "table",
    quote_ident(a.attname) AS "column",
    pg_get_expr(d.adbin, d.adrelid) AS "default"
FROM pg_catalog.pg_attribute a
    JOIN pg_catalog.pg_class c ON a.attrelid = c.oid
    JOIN pg_catalog.pg_type t ON a.atttypid = t.oid
    JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
    JOIN pg_catalog.pg_enum e ON t.oid = e.enumtypid
    LEFT JOIN pg_catalog.pg_attrdef d ON c.oid = d.adrelid AND a.attnum = d.adnum
WHERE n.nspname NOT IN ('pg_catalog', 'information_schema', 'pg_toast')
    AND n.nspname = ANY(current_schemas(false))
    AND a.attnum > 0
    AND c.relkind = 'm'
    AND NOT a.attisdropped
;
QUERY;

    protected function getUsageQuery(): string
    {
        return self::MATERIAL_VIEWS_USING_TYPES_QUERY;
    }
}
