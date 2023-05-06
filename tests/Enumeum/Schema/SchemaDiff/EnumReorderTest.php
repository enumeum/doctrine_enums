<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EnumeumTests\Schema\SchemaDiff;

use Doctrine\DBAL\Exception\DriverException;
use Enumeum\DoctrineEnum\Definition\Definition;
use Enumeum\DoctrineEnum\EnumUsage\Usage;
use Enumeum\DoctrineEnum\EnumUsage\UsageColumn;
use Enumeum\DoctrineEnum\Schema\DefinitionDiff;
use Enumeum\DoctrineEnum\Schema\SchemaDiff;
use EnumeumTests\Setup\BaseTestCaseSchema;

final class EnumReorderTest extends BaseTestCaseSchema
{
    public function testEnumTypeNotUsedAnywhere(): void
    {
        $this->applySQL([]);

        $diff = new SchemaDiff(
            alterChangeSet: [
                new DefinitionDiff(
                    new Definition('status_type', ['started', 'processing', 'finished']),
                    new Definition('status_type', ['started', 'finished']),
                    null,
                ),
            ],
        );

        $updateSql = $diff->toSql();

        self::assertSame(
            [
                'ALTER TYPE status_type RENAME TO status_type__',
                "CREATE TYPE status_type AS ENUM ('started', 'finished')",
                'DROP TYPE IF EXISTS status_type__',
            ],
            $updateSql,
        );

        $this->applySQL($updateSql);
    }

    public function testEnumTypeRemovedValuesWithKeptOrdering(): void
    {
        $this->applySQL([]);

        $diff = new SchemaDiff(
            alterChangeSet: [
                new DefinitionDiff(
                    new Definition('status_type', ['started', 'processing', 'finished']),
                    new Definition('status_type', ['started', 'processing']),
                    null,
                ),
            ],
        );

        $updateSql = $diff->toSql();

        self::assertSame(
            [
                'ALTER TYPE status_type RENAME TO status_type__',
                "CREATE TYPE status_type AS ENUM ('started', 'processing')",
                'DROP TYPE IF EXISTS status_type__',
            ],
            $updateSql,
        );

        $this->applySQL($updateSql);
    }

    public function testEnumTypeSameValuesWithOtherOrdering(): void
    {
        $this->applySQL([]);

        $diff = new SchemaDiff(
            alterChangeSet: [
                new DefinitionDiff(
                    new Definition('status_type', ['started', 'processing', 'finished']),
                    new Definition('status_type', ['started', 'finished', 'processing']),
                    null,
                ),
            ],
        );

        $updateSql = $diff->toSql();

        self::assertSame(
            [
                'ALTER TYPE status_type RENAME TO status_type__',
                "CREATE TYPE status_type AS ENUM ('started', 'finished', 'processing')",
                'DROP TYPE IF EXISTS status_type__',
            ],
            $updateSql,
        );

        $this->applySQL($updateSql);
    }

    public function testEnumTypeUsedByEmptyTable(): void
    {
        $this->applySQL([
            'CREATE TABLE entity (id INT NOT NULL, PRIMARY KEY(id), status status_type NOT NULL)',
        ]);

        $diff = new SchemaDiff(
            alterChangeSet: [
                new DefinitionDiff(
                    new Definition('status_type', ['started', 'processing', 'finished']),
                    new Definition('status_type', ['started', 'finished']),
                    new Usage('status_type', [
                        new UsageColumn('status_type', 'entity', 'status'),
                    ]),
                ),
            ],
        );

        $updateSql = $diff->toSql();

        self::assertSame(
            [
                'ALTER TYPE status_type RENAME TO status_type__',
                "CREATE TYPE status_type AS ENUM ('started', 'finished')",
                'LOCK TABLE entity',
                'ALTER TABLE entity ALTER COLUMN status TYPE status_type USING status::text::status_type',
                'DROP TYPE IF EXISTS status_type__',
            ],
            $updateSql,
        );

        $this->applySQLWithinTransaction($updateSql);
    }

    public function testEnumTypeUsedByEmptyTableWithDefault(): void
    {
        $this->applySQL([
            "CREATE TABLE entity (id INT NOT NULL, PRIMARY KEY(id), status status_type NOT NULL DEFAULT 'started'::status_type)",
        ]);

        $diff = new SchemaDiff(
            alterChangeSet: [
                new DefinitionDiff(
                    new Definition('status_type', ['started', 'processing', 'finished']),
                    new Definition('status_type', ['started', 'finished']),
                    new Usage('status_type', [
                        new UsageColumn('status_type', 'entity', 'status', '\'started\'::status_type'),
                    ]),
                ),
            ],
        );

        $updateSql = $diff->toSql();

        self::assertSame(
            [
                'ALTER TYPE status_type RENAME TO status_type__',
                "CREATE TYPE status_type AS ENUM ('started', 'finished')",
                'LOCK TABLE entity',
                'ALTER TABLE entity ALTER COLUMN status DROP DEFAULT',
                'ALTER TABLE entity ALTER COLUMN status TYPE status_type USING status::text::status_type',
                "ALTER TABLE entity ALTER COLUMN status SET DEFAULT 'started'::status_type",
                'DROP TYPE IF EXISTS status_type__',
            ],
            $updateSql,
        );

        $this->applySQLWithinTransaction($updateSql);
    }

    /**
     * TODO: Probably need to add check of impossible data update.
     */
    public function testEnumTypeUsedByEmptyTableWithNotExistingDefault(): void
    {
        $this->applySQL([
            "CREATE TABLE entity (id INT NOT NULL, PRIMARY KEY(id), status status_type NOT NULL DEFAULT 'processing'::status_type)",
        ]);

        $diff = new SchemaDiff(
            alterChangeSet: [
                new DefinitionDiff(
                    new Definition('status_type', ['started', 'processing', 'finished']),
                    new Definition('status_type', ['started', 'finished']),
                    new Usage('status_type', [
                        new UsageColumn('status_type', 'entity', 'status', '\'processing\'::status_type'),
                    ]),
                ),
            ],
        );

        $updateSql = $diff->toSql();

        self::assertSame(
            [
                'ALTER TYPE status_type RENAME TO status_type__',
                "CREATE TYPE status_type AS ENUM ('started', 'finished')",
                'LOCK TABLE entity',
                'ALTER TABLE entity ALTER COLUMN status DROP DEFAULT',
                'ALTER TABLE entity ALTER COLUMN status TYPE status_type USING status::text::status_type',
                "ALTER TABLE entity ALTER COLUMN status SET DEFAULT 'processing'::status_type",
                'DROP TYPE IF EXISTS status_type__',
            ],
            $updateSql,
        );

        self::expectException(DriverException::class);
        self::expectExceptionMessage(
            'An exception occurred while executing a query: SQLSTATE[22P02]: Invalid text representation: 7 ERROR:  invalid input value for enum status_type: "processing"',
        );

        $this->applySQLWithinTransaction($updateSql);
    }

    public function testEnumTypeUsedByTableWithRecords(): void
    {
        $this->applySQL([
            'CREATE TABLE entity (id INT NOT NULL, PRIMARY KEY(id), status status_type NOT NULL)',
            "INSERT INTO entity (id, status) VALUES (1, 'started')",
            "INSERT INTO entity (id, status) VALUES (3, 'finished')",
        ]);

        $diff = new SchemaDiff(
            alterChangeSet: [
                new DefinitionDiff(
                    new Definition('status_type', ['started', 'processing', 'finished']),
                    new Definition('status_type', ['started', 'finished']),
                    new Usage('status_type', [
                        new UsageColumn('status_type', 'entity', 'status'),
                    ]),
                ),
            ],
        );

        $updateSql = $diff->toSql();

        self::assertSame(
            [
                'ALTER TYPE status_type RENAME TO status_type__',
                "CREATE TYPE status_type AS ENUM ('started', 'finished')",
                'LOCK TABLE entity',
                'ALTER TABLE entity ALTER COLUMN status TYPE status_type USING status::text::status_type',
                'DROP TYPE IF EXISTS status_type__',
            ],
            $updateSql,
        );

        $this->applySQLWithinTransaction($updateSql);
    }

    public function testEnumTypeUsedByTableWithRecordsAndDefault(): void
    {
        $this->applySQL([
            "CREATE TABLE entity (id INT NOT NULL, PRIMARY KEY(id), status status_type NOT NULL DEFAULT 'started'::status_type)",
            "INSERT INTO entity (id, status) VALUES (1, 'started')",
            "INSERT INTO entity (id, status) VALUES (3, 'finished')",
        ]);

        $diff = new SchemaDiff(
            alterChangeSet: [
                new DefinitionDiff(
                    new Definition('status_type', ['started', 'processing', 'finished']),
                    new Definition('status_type', ['started', 'finished']),
                    new Usage('status_type', [
                        new UsageColumn('status_type', 'entity', 'status', '\'started\'::status_type'),
                    ]),
                ),
            ],
        );

        $updateSql = $diff->toSql();

        self::assertSame(
            [
                'ALTER TYPE status_type RENAME TO status_type__',
                "CREATE TYPE status_type AS ENUM ('started', 'finished')",
                'LOCK TABLE entity',
                'ALTER TABLE entity ALTER COLUMN status DROP DEFAULT',
                'ALTER TABLE entity ALTER COLUMN status TYPE status_type USING status::text::status_type',
                "ALTER TABLE entity ALTER COLUMN status SET DEFAULT 'started'::status_type",
                'DROP TYPE IF EXISTS status_type__',
            ],
            $updateSql,
        );

        $this->applySQLWithinTransaction($updateSql);
    }

    public function testEnumTypeUsedByManyTablesWithRecordsAndDefault(): void
    {
        $this->applySQL([
            "CREATE TABLE entity (id INT NOT NULL, PRIMARY KEY(id), status status_type NOT NULL DEFAULT 'started'::status_type)",
            "INSERT INTO entity (id, status) VALUES (1, 'started')",
            "INSERT INTO entity (id, status) VALUES (3, 'finished')",
            "CREATE TABLE entity_one (id INT NOT NULL, PRIMARY KEY(id), status status_type NOT NULL DEFAULT 'finished'::status_type)",
            "INSERT INTO entity_one (id, status) VALUES (1, 'started')",
            "INSERT INTO entity_one (id, status) VALUES (3, 'finished')",
        ]);

        $diff = new SchemaDiff(
            alterChangeSet: [
                new DefinitionDiff(
                    new Definition('status_type', ['started', 'processing', 'finished']),
                    new Definition('status_type', ['started', 'finished']),
                    new Usage('status_type', [
                        new UsageColumn('status_type', 'entity', 'status', '\'started\'::status_type'),
                        new UsageColumn('status_type', 'entity_one', 'status', '\'finished\'::status_type'),
                    ]),
                ),
            ],
        );

        $updateSql = $diff->toSql();

        self::assertSame(
            [
                'ALTER TYPE status_type RENAME TO status_type__',
                "CREATE TYPE status_type AS ENUM ('started', 'finished')",
                'LOCK TABLE entity',
                'ALTER TABLE entity ALTER COLUMN status DROP DEFAULT',
                'ALTER TABLE entity ALTER COLUMN status TYPE status_type USING status::text::status_type',
                "ALTER TABLE entity ALTER COLUMN status SET DEFAULT 'started'::status_type",
                'LOCK TABLE entity_one',
                'ALTER TABLE entity_one ALTER COLUMN status DROP DEFAULT',
                'ALTER TABLE entity_one ALTER COLUMN status TYPE status_type USING status::text::status_type',
                "ALTER TABLE entity_one ALTER COLUMN status SET DEFAULT 'finished'::status_type",
                'DROP TYPE IF EXISTS status_type__',
            ],
            $updateSql,
        );

        $this->applySQLWithinTransaction($updateSql);
    }

    /**
     * TODO: Probably need to add check of impossible data update.
     */
    public function testEnumTypeUsedByTableWithRecordsHavingRemovedValues(): void
    {
        $this->applySQL([
            'CREATE TABLE entity (id INT NOT NULL, PRIMARY KEY(id), status status_type NOT NULL)',
            "INSERT INTO entity (id, status) VALUES (1, 'started')",
            "INSERT INTO entity (id, status) VALUES (2, 'processing')",
            "INSERT INTO entity (id, status) VALUES (3, 'finished')",
        ]);

        $diff = new SchemaDiff(
            alterChangeSet: [
                new DefinitionDiff(
                    new Definition('status_type', ['started', 'processing', 'finished']),
                    new Definition('status_type', ['started', 'finished']),
                    new Usage('status_type', [
                        new UsageColumn('status_type', 'entity', 'status'),
                    ]),
                ),
            ],
        );

        $updateSql = $diff->toSql();

        self::assertSame(
            [
                'ALTER TYPE status_type RENAME TO status_type__',
                "CREATE TYPE status_type AS ENUM ('started', 'finished')",
                'LOCK TABLE entity',
                'ALTER TABLE entity ALTER COLUMN status TYPE status_type USING status::text::status_type',
                'DROP TYPE IF EXISTS status_type__',
            ],
            $updateSql,
        );

        self::expectException(DriverException::class);
        self::expectExceptionMessage(
            'An exception occurred while executing a query: SQLSTATE[22P02]: Invalid text representation: 7 ERROR:  invalid input value for enum status_type: "processing"',
        );

        $this->applySQLWithinTransaction($updateSql);
    }

    protected function getBaseSQL(): array
    {
        return [
            "CREATE TYPE status_type AS ENUM ('started', 'processing', 'finished')",
        ];
    }
}
