<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EnumeumTests\QueryBuild;

use Enumeum\DoctrineEnum\QueryBuild\ColumnDefaultQueryBuilder;
use EnumeumTests\Setup\BaseTestCaseSchema;

final class ColumnDefaultQueryBuilderTest extends BaseTestCaseSchema
{
    public function testBuildDropColumnDefaultSql(): void
    {
        $updateSql = ColumnDefaultQueryBuilder::buildDropColumnDefaultSql('some_table', 'some_column');

        self::assertSame(
            [
                "ALTER TABLE some_table ALTER COLUMN some_column DROP DEFAULT",
            ],
            $updateSql,
        );

        $this->applySQL([
            "CREATE TYPE some_type AS ENUM ('one', 'two', 'three')",
            "CREATE TABLE some_table (id INT NOT NULL, PRIMARY KEY(id), some_column some_type NOT NULL DEFAULT 'one'::some_type)",
            "INSERT INTO some_table VALUES (1)",
        ]);

        $this->applySQL($updateSql);
    }

    public function testBuildSetColumnDefaultSql(): void
    {
        $updateSql = ColumnDefaultQueryBuilder::buildSetColumnDefaultSql('some_table', 'some_column', '\'one\'::some_type');

        self::assertSame(
            [
                "ALTER TABLE some_table ALTER COLUMN some_column SET DEFAULT 'one'::some_type",
            ],
            $updateSql,
        );

        $this->applySQL([
            "CREATE TYPE some_type AS ENUM ('one', 'two', 'three')",
            "CREATE TABLE some_table (id INT NOT NULL, PRIMARY KEY(id), some_column some_type NOT NULL)",
            "INSERT INTO some_table VALUES (1, 'two')",
        ]);

        $this->applySQL($updateSql);
    }

    protected function getBaseSQL(): array
    {
        return [];
    }
}
