<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EnumeumTests\QueryBuild;

use Enumeum\DoctrineEnum\Definition\Definition;
use Enumeum\DoctrineEnum\QueryBuild\EnumQueryBuilder;
use EnumeumTests\Setup\BaseTestCaseSchema;

final class EnumQueryBuilderTest extends BaseTestCaseSchema
{
    public function testBuildEnumTypeCreateSql(): void
    {
        $updateSql = EnumQueryBuilder::buildEnumTypeCreateSql(
            new Definition('some_type', ['one', 'two', 'three']),
        );

        self::assertSame(
            [
                "CREATE TYPE some_type AS ENUM ('one', 'two', 'three')",
            ],
            $updateSql,
        );

        $this->applySQL($updateSql);
    }

    public function testBuildEnumTypeAlterSql(): void
    {
        $updateSql = EnumQueryBuilder::buildEnumTypeAlterSql(
            new Definition('some_type', ['one', 'two', 'three']),
            new Definition('some_type', ['one', 'two', 'three', 'four']),
        );

        self::assertSame(
            [
                "ALTER TYPE some_type ADD VALUE IF NOT EXISTS 'four'",
            ],
            $updateSql,
        );

        $this->applySQL([
            "CREATE TYPE some_type AS ENUM ('one', 'two', 'three')",
        ]);

        $this->applySQL($updateSql);
    }

    public function testBuildEnumTypeDropSql(): void
    {
        $updateSql = EnumQueryBuilder::buildEnumTypeDropSql(
            new Definition('some_type', []),
        );

        self::assertSame(
            [
                "DROP TYPE IF EXISTS some_type",
            ],
            $updateSql,
        );

        $this->applySQL([
            "CREATE TYPE some_type AS ENUM ('one', 'two', 'three')",
        ]);

        $this->applySQL($updateSql);
    }

    public function testBuildEnumTypeRenameToTemporarySql(): void
    {
        $updateSql = EnumQueryBuilder::buildEnumTypeRenameToTemporarySql(
            new Definition('some_type', []),
        );

        self::assertSame(
            [
                "ALTER TYPE some_type RENAME TO some_type__",
            ],
            $updateSql,
        );

        $this->applySQL([
            "CREATE TYPE some_type AS ENUM ('one', 'two', 'three')",
        ]);

        $this->applySQL($updateSql);
    }

    public function testBuildEnumTypeDropTemporarySql(): void
    {
        $updateSql = EnumQueryBuilder::buildEnumTypeDropTemporarySql(
            new Definition('some_type', []),
        );

        self::assertSame(
            [
                "DROP TYPE IF EXISTS some_type__",
            ],
            $updateSql,
        );

        $this->applySQL([
            "CREATE TYPE some_type AS ENUM ('one', 'two', 'three')",
            "ALTER TYPE some_type RENAME TO some_type__",
        ]);

        $this->applySQL($updateSql);
    }

    public function testBuildEnumTypeAlterColumnSql(): void
    {
        $updateSql = EnumQueryBuilder::buildEnumTypeAlterColumnSql(
            new Definition('some_type', []),
            'some_table',
            'some_column',
        );

        self::assertSame(
            [
                "ALTER TABLE some_table ALTER COLUMN some_column TYPE some_type USING some_column::text::some_type",
            ],
            $updateSql,
        );

        $this->applySQL([
            "CREATE TYPE some_type AS ENUM ('one', 'two', 'three')",
            "CREATE TABLE some_table (id INT NOT NULL, PRIMARY KEY(id), some_column some_type NOT NULL DEFAULT 'one'::some_type)",
            "INSERT INTO some_table VALUES (1, 'two')",
        ]);

        $this->applySQL($updateSql);
    }

    protected function getBaseSQL(): array
    {
        return [];
    }
}
