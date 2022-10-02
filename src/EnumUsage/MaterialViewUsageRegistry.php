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
SELECT format_type(att.atttypid, att.atttypmod) as "name",
    mv.relname as "table",
    att.attname as "column"
FROM pg_catalog.pg_class mv
    JOIN pg_catalog.pg_attribute att ON mv.oid = att.attrelid
    JOIN pg_catalog.pg_namespace nsp ON nsp.oid = mv.relnamespace
    JOIN pg_catalog.pg_enum enu ON att.atttypid = enu.enumtypid
WHERE nsp.nspname NOT IN ('information_schema', 'pg_catalog')
    AND mv.relkind = 'm'
    AND NOT att.attisdropped
    AND att.attnum > 0
GROUP BY mv.relname,
    att.attname,
    att.atttypid,
    att.atttypmod
;
QUERY;

    protected function getUsageQuery(): string
    {
        return self::MATERIAL_VIEWS_USING_TYPES_QUERY;
    }
}
