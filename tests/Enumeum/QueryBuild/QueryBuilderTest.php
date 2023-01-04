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
use Enumeum\DoctrineEnum\EnumUsage\Usage;
use Enumeum\DoctrineEnum\EnumUsage\UsageColumn;
use Enumeum\DoctrineEnum\Schema\DefinitionDiff;
use EnumeumTests\Setup\BaseTestCaseSchema;

final class QueryBuilderTest extends BaseTestCaseSchema
{
    public function testGenerateEnumCreateQueries(): void
    {
        $definitions = [
            new Definition('some_type', ['one', 'two', 'three']),
            new Definition('other_type', ['один', 'два', 'три']),
        ];

        $updateSql = $this->getQueryBuilder()->generateEnumCreateQueries($definitions);

        self::assertSame(
            [
                "CREATE TYPE some_type AS ENUM ('one', 'two', 'three')",
                "CREATE TYPE other_type AS ENUM ('один', 'два', 'три')",
            ],
            $updateSql,
        );

        $this->applySQL($updateSql);
    }

    public function testGenerateEnumCreateQueriesNoDefinitions(): void
    {
        $definitions = [];

        $updateSql = $this->getQueryBuilder()->generateEnumCreateQueries($definitions);

        self::assertSame(
            [],
            $updateSql,
        );

        $this->applySQL($updateSql);
    }

    public function testGenerateEnumAddValuesQueries(): void
    {
        $this->applySQL([
            "CREATE TYPE some_type AS ENUM ('one', 'two', 'three')",
            "CREATE TYPE other_type AS ENUM ('один', 'два', 'три')",
        ]);

        $diffs = [
            new DefinitionDiff(
                new Definition('some_type', ['one', 'two', 'three']),
                new Definition('some_type', ['one', 'two', 'three', 'four']),
            ),
            new DefinitionDiff(
                new Definition('other_type', ['один', 'два', 'три']),
                new Definition('other_type', ['один', 'два', 'три', 'четыре']),
            ),
        ];

        $updateSql = $this->getQueryBuilder()->generateEnumAddValuesQueries($diffs);

        self::assertSame(
            [
                "ALTER TYPE some_type ADD VALUE IF NOT EXISTS 'four'",
                "ALTER TYPE other_type ADD VALUE IF NOT EXISTS 'четыре'",
            ],
            $updateSql,
        );

        $this->applySQL($updateSql);
    }

    public function testGenerateEnumAddValuesQueriesNoDiffs(): void
    {
        $diffs = [];
        $updateSql = $this->getQueryBuilder()->generateEnumAddValuesQueries($diffs);

        self::assertSame(
            [],
            $updateSql,
        );
    }

    public function testGenerateEnumReorderQueriesWithoutUsage(): void
    {
        $this->applySQL([
            "CREATE TYPE some_type AS ENUM ('one', 'two', 'three')",
            "CREATE TYPE other_type AS ENUM ('один', 'два', 'три')",
        ]);

        $diffs = [
            new DefinitionDiff(
                new Definition('some_type', ['one', 'two', 'three']),
                new Definition('some_type', ['one', 'three', 'four']),
                null,
            ),
            new DefinitionDiff(
                new Definition('other_type', ['один', 'два', 'три']),
                new Definition('other_type', ['один', 'три', 'четыре']),
                null,
            ),
        ];

        $updateSql = $this->getQueryBuilder()->generateEnumReorderQueries($diffs);

        self::assertSame(
            [
                'ALTER TYPE some_type RENAME TO some_type__',
                "CREATE TYPE some_type AS ENUM ('one', 'three', 'four')",
                'DROP TYPE IF EXISTS some_type__',
                'ALTER TYPE other_type RENAME TO other_type__',
                "CREATE TYPE other_type AS ENUM ('один', 'три', 'четыре')",
                'DROP TYPE IF EXISTS other_type__',
            ],
            $updateSql,
        );

        $this->applySQL($updateSql);
    }

    public function testGenerateEnumReorderQueriesWithUsage(): void
    {
        $this->applySQL([
            "CREATE TYPE some_type AS ENUM ('one', 'two', 'three')",
            "CREATE TYPE other_type AS ENUM ('один', 'два', 'три')",
            "CREATE TABLE some_table (id INT NOT NULL, PRIMARY KEY(id), some_column some_type NOT NULL DEFAULT 'one'::some_type)",
            "INSERT INTO some_table VALUES (1, 'three')",
            "CREATE TABLE other_table (id INT NOT NULL, PRIMARY KEY(id), other_column other_type NOT NULL DEFAULT 'один'::other_type)",
            "INSERT INTO other_table VALUES (1, 'три')",
        ]);

        $diffs = [
            new DefinitionDiff(
                new Definition('some_type', ['one', 'two', 'three']),
                new Definition('some_type', ['one', 'three', 'four']),
                new Usage('some_type', [
                    new UsageColumn('some_type', 'some_table', 'some_column', '\'one\'::some_type'),
                ]),
            ),
            new DefinitionDiff(
                new Definition('other_type', ['один', 'два', 'три']),
                new Definition('other_type', ['один', 'три', 'четыре']),
                new Usage('other_type', [
                    new UsageColumn('other_type', 'other_table', 'other_column', '\'один\'::other_type'),
                ]),
            ),
        ];

        $updateSql = $this->getQueryBuilder()->generateEnumReorderQueries($diffs);

        self::assertSame(
            [
                'ALTER TYPE some_type RENAME TO some_type__',
                "CREATE TYPE some_type AS ENUM ('one', 'three', 'four')",
                'LOCK TABLE some_table',
                'ALTER TABLE some_table ALTER COLUMN some_column DROP DEFAULT',
                'ALTER TABLE some_table ALTER COLUMN some_column TYPE some_type USING some_column::text::some_type',
                "ALTER TABLE some_table ALTER COLUMN some_column SET DEFAULT 'one'::some_type",
                'DROP TYPE IF EXISTS some_type__',

                'ALTER TYPE other_type RENAME TO other_type__',
                "CREATE TYPE other_type AS ENUM ('один', 'три', 'четыре')",
                'LOCK TABLE other_table',
                'ALTER TABLE other_table ALTER COLUMN other_column DROP DEFAULT',
                'ALTER TABLE other_table ALTER COLUMN other_column TYPE other_type USING other_column::text::other_type',
                "ALTER TABLE other_table ALTER COLUMN other_column SET DEFAULT 'один'::other_type",
                'DROP TYPE IF EXISTS other_type__',
            ],
            $updateSql,
        );

        $this->applySQLWithinTransaction($updateSql);
    }

    public function testGenerateEnumReorderQueriesNoDiffs(): void
    {
        $diffs = [];
        $updateSql = $this->getQueryBuilder()->generateEnumReorderQueries($diffs);

        self::assertSame(
            [],
            $updateSql,
        );
    }

    public function testGenerateEnumAlterAddValuesQueries(): void
    {
        $this->applySQL([
            "CREATE TYPE some_type AS ENUM ('one', 'two', 'three')",
            "CREATE TYPE other_type AS ENUM ('один', 'два', 'три')",
        ]);

        $diffs = [
            new DefinitionDiff(
                new Definition('some_type', ['one', 'two', 'three']),
                new Definition('some_type', ['one', 'two', 'three', 'four']),
            ),
            new DefinitionDiff(
                new Definition('other_type', ['один', 'два', 'три']),
                new Definition('other_type', ['один', 'два', 'три', 'четыре']),
            ),
        ];

        $updateSql = $this->getQueryBuilder()->generateEnumAlterQueries($diffs);

        self::assertSame(
            [
                "ALTER TYPE some_type ADD VALUE IF NOT EXISTS 'four'",
                "ALTER TYPE other_type ADD VALUE IF NOT EXISTS 'четыре'",
            ],
            $updateSql,
        );

        $this->applySQL($updateSql);
    }

    public function testGenerateEnumAlterReorderingQueriesWithoutUsage(): void
    {
        $this->applySQL([
            "CREATE TYPE some_type AS ENUM ('one', 'two', 'three')",
            "CREATE TYPE other_type AS ENUM ('один', 'два', 'три')",
        ]);

        $diffs = [
            new DefinitionDiff(
                new Definition('some_type', ['one', 'two', 'three']),
                new Definition('some_type', ['one', 'three', 'four']),
                null,
            ),
            new DefinitionDiff(
                new Definition('other_type', ['один', 'два', 'три']),
                new Definition('other_type', ['один', 'три', 'четыре']),
                null,
            ),
        ];

        $updateSql = $this->getQueryBuilder()->generateEnumAlterQueries($diffs);

        self::assertSame(
            [
                'ALTER TYPE some_type RENAME TO some_type__',
                "CREATE TYPE some_type AS ENUM ('one', 'three', 'four')",
                'DROP TYPE IF EXISTS some_type__',
                'ALTER TYPE other_type RENAME TO other_type__',
                "CREATE TYPE other_type AS ENUM ('один', 'три', 'четыре')",
                'DROP TYPE IF EXISTS other_type__',
            ],
            $updateSql,
        );

        $this->applySQL($updateSql);
    }

    public function testGenerateEnumAlterReorderingQueriesWithUsage(): void
    {
        $this->applySQL([
            "CREATE TYPE some_type AS ENUM ('one', 'two', 'three')",
            "CREATE TYPE other_type AS ENUM ('один', 'два', 'три')",
            "CREATE TABLE some_table (id INT NOT NULL, PRIMARY KEY(id), some_column some_type NOT NULL DEFAULT 'one'::some_type)",
            "INSERT INTO some_table VALUES (1, 'three')",
            "CREATE TABLE other_table (id INT NOT NULL, PRIMARY KEY(id), other_column other_type NOT NULL DEFAULT 'один'::other_type)",
            "INSERT INTO other_table VALUES (1, 'три')",
        ]);

        $diffs = [
            new DefinitionDiff(
                new Definition('some_type', ['one', 'two', 'three']),
                new Definition('some_type', ['one', 'three', 'four']),
                new Usage('some_type', [
                    new UsageColumn('some_type', 'some_table', 'some_column', '\'one\'::some_type'),
                ]),
            ),
            new DefinitionDiff(
                new Definition('other_type', ['один', 'два', 'три']),
                new Definition('other_type', ['один', 'три', 'четыре']),
                new Usage('other_type', [
                    new UsageColumn('other_type', 'other_table', 'other_column', '\'один\'::other_type'),
                ]),
            ),
        ];

        $updateSql = $this->getQueryBuilder()->generateEnumAlterQueries($diffs);

        self::assertSame(
            [
                'ALTER TYPE some_type RENAME TO some_type__',
                "CREATE TYPE some_type AS ENUM ('one', 'three', 'four')",
                'LOCK TABLE some_table',
                'ALTER TABLE some_table ALTER COLUMN some_column DROP DEFAULT',
                'ALTER TABLE some_table ALTER COLUMN some_column TYPE some_type USING some_column::text::some_type',
                "ALTER TABLE some_table ALTER COLUMN some_column SET DEFAULT 'one'::some_type",
                'DROP TYPE IF EXISTS some_type__',

                'ALTER TYPE other_type RENAME TO other_type__',
                "CREATE TYPE other_type AS ENUM ('один', 'три', 'четыре')",
                'LOCK TABLE other_table',
                'ALTER TABLE other_table ALTER COLUMN other_column DROP DEFAULT',
                'ALTER TABLE other_table ALTER COLUMN other_column TYPE other_type USING other_column::text::other_type',
                "ALTER TABLE other_table ALTER COLUMN other_column SET DEFAULT 'один'::other_type",
                'DROP TYPE IF EXISTS other_type__',
            ],
            $updateSql,
        );

        $this->applySQLWithinTransaction($updateSql);
    }

    public function testGenerateEnumAlterQueriesNoDiffs(): void
    {
        $diffs = [];
        $updateSql = $this->getQueryBuilder()->generateEnumAlterQueries($diffs);

        self::assertSame(
            [],
            $updateSql,
        );
    }

    public function testGenerateEnumDropQueries(): void
    {
        $definitions = [
            new Definition('some_type', []),
            new Definition('other_type', []),
        ];

        $updateSql = $this->getQueryBuilder()->generateEnumDropQueries($definitions);

        self::assertSame(
            [
                'DROP TYPE IF EXISTS some_type',
                'DROP TYPE IF EXISTS other_type',
            ],
            $updateSql,
        );

        $this->applySQL([
            "CREATE TYPE some_type AS ENUM ('one', 'two', 'three')",
            "CREATE TYPE other_type AS ENUM ('один', 'два', 'три')",
        ]);

        $this->applySQL($updateSql);
    }

    public function testGenerateEnumDropQueriesNoDefinitions(): void
    {
        $diffs = [];
        $updateSql = $this->getQueryBuilder()->generateEnumDropQueries($diffs);

        self::assertSame(
            [],
            $updateSql,
        );
    }

    protected function getBaseSQL(): array
    {
        return [];
    }
}
