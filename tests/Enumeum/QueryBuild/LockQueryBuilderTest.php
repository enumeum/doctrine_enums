<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EnumeumTests\QueryBuild;

use Enumeum\DoctrineEnum\QueryBuild\LockQueryBuilder;
use EnumeumTests\Setup\BaseTestCaseSchema;

final class LockQueryBuilderTest extends BaseTestCaseSchema
{
    public function testBuildEnumTypeCreateSql(): void
    {
        $updateSql = LockQueryBuilder::buildLockTableSql('some_table');

        self::assertSame(
            [
                "LOCK TABLE some_table",
            ],
            $updateSql,
        );

        $this->applySQL([
            "CREATE TABLE some_table (id INT NOT NULL, PRIMARY KEY(id))",
            "INSERT INTO some_table VALUES (1)",
        ]);

        $this->applySQLWithinTransaction($updateSql);
    }

    protected function getBaseSQL(): array
    {
        return [];
    }
}
