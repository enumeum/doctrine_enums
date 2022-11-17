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

class LockQueryBuilder
{
    private const LOCK_TABLE_QUERY = 'LOCK TABLE %1$s';

    public static function buildLockTableSql(string $table): iterable
    {
        return [sprintf(self::LOCK_TABLE_QUERY, $table)];
    }
}
