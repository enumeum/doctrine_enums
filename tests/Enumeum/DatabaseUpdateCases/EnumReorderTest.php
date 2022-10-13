<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine enumerations extension for Postgres" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EnumeumTests\DatabaseUpdateCases;

use Doctrine\DBAL\Exception\DriverException;
use EnumeumTests\Fixture\AddedValuesStatusType;
use EnumeumTests\Fixture\BaseStatusType;
use EnumeumTests\Fixture\RemovedValuesStatusType;
use EnumeumTests\Setup\BaseTestCaseSchemaPostgres13;

final class EnumReorderTest extends BaseTestCaseSchemaPostgres13
{
    public function testEnumTypeNotUsedAnywhere(): void
    {
        $this->getDefinitionRegistry()->loadType(RemovedValuesStatusType::class);

        $updateSql = $this->getDatabaseUpdateQueryBuilder()->generateEnumReorderQueries();

        self::assertSame(
            [
                "ALTER TYPE status_type RENAME TO status_type__",
                "CREATE TYPE status_type AS ENUM ('started', 'finished')",
                "DROP TYPE status_type__",
            ],
            $updateSql,
        );

        $this->applySQL($updateSql);
    }

    public function testEnumTypeUsedByEmptyTable(): void
    {
        $this->applySQL([
            "CREATE TABLE entity (id INT NOT NULL, PRIMARY KEY(id), status status_type NOT NULL)",
        ]);

        $this->getDefinitionRegistry()->loadType(RemovedValuesStatusType::class);

        $updateSql = $this->getDatabaseUpdateQueryBuilder()->generateEnumReorderQueries();

        self::assertSame(
            [
                "ALTER TYPE status_type RENAME TO status_type__",
                "CREATE TYPE status_type AS ENUM ('started', 'finished')",
                "LOCK TABLE entity",
                "ALTER TABLE entity ALTER COLUMN status DROP DEFAULT",
                "ALTER TABLE entity ALTER COLUMN status TYPE status_type USING status::text::status_type",
                "DROP TYPE status_type__",
            ],
            $updateSql,
        );

        $this->applySQLWithinTransaction($updateSql);
    }

    public function testEnumTypeUsedByTableWithRecords(): void
    {
        $this->applySQL([
            "CREATE TABLE entity (id INT NOT NULL, PRIMARY KEY(id), status status_type NOT NULL)",
            "INSERT INTO entity (id, status) VALUES (1, 'started')",
//            "INSERT INTO entity (id, status) VALUES (2, 'processing')",
            "INSERT INTO entity (id, status) VALUES (3, 'finished')",
        ]);

        $this->getDefinitionRegistry()->loadType(RemovedValuesStatusType::class);

        $updateSql = $this->getDatabaseUpdateQueryBuilder()->generateEnumReorderQueries();

        self::assertSame(
            [
                "ALTER TYPE status_type RENAME TO status_type__",
                "CREATE TYPE status_type AS ENUM ('started', 'finished')",
                "LOCK TABLE entity",
                "ALTER TABLE entity ALTER COLUMN status DROP DEFAULT",
                "ALTER TABLE entity ALTER COLUMN status TYPE status_type USING status::text::status_type",
                "DROP TYPE status_type__",
            ],
            $updateSql,
        );

        $this->applySQLWithinTransaction($updateSql);
    }

    /**
     * TODO: Probably need to add check of impossible data update
     */
    public function testEnumTypeUsedByTableWithRecordsHavingRemovedValues(): void
    {
        $this->applySQL([
            "CREATE TABLE entity (id INT NOT NULL, PRIMARY KEY(id), status status_type NOT NULL)",
            "INSERT INTO entity (id, status) VALUES (1, 'started')",
            "INSERT INTO entity (id, status) VALUES (2, 'processing')",
            "INSERT INTO entity (id, status) VALUES (3, 'finished')",
        ]);

        $this->getDefinitionRegistry()->loadType(RemovedValuesStatusType::class);

        $updateSql = $this->getDatabaseUpdateQueryBuilder()->generateEnumReorderQueries();

        self::assertSame(
            [
                "ALTER TYPE status_type RENAME TO status_type__",
                "CREATE TYPE status_type AS ENUM ('started', 'finished')",
                "LOCK TABLE entity",
                "ALTER TABLE entity ALTER COLUMN status DROP DEFAULT",
                "ALTER TABLE entity ALTER COLUMN status TYPE status_type USING status::text::status_type",
                "DROP TYPE status_type__",
            ],
            $updateSql,
        );

        $this->expectException(DriverException::class);
        $this->expectExceptionMessage(
            'An exception occurred while executing a query: SQLSTATE[22P02]: Invalid text representation: 7 ERROR:  invalid input value for enum status_type: "processing"',
        );

        $this->applySQLWithinTransaction($updateSql);
    }

    public function testEnumTypeCreating(): void
    {
        $this->applySQL([
            "DROP TYPE status_type",
        ]);

        $this->getDefinitionRegistry()->loadType(BaseStatusType::class);

        $updateSql = $this->getDatabaseUpdateQueryBuilder()->generateEnumReorderQueries();

        self::assertSame(
            [],
            $updateSql,
        );

        $this->applySQL($updateSql);
    }

    public function testEnumTypeAltering(): void
    {
        $this->getDefinitionRegistry()->loadType(AddedValuesStatusType::class);

        $updateSql = $this->getDatabaseUpdateQueryBuilder()->generateEnumReorderQueries();

        self::assertSame(
            [],
            $updateSql,
        );

        $this->applySQL($updateSql);
    }

    public function testEnumTypeDropping(): void
    {
        /** There is no any loaded type. */
        /** $this->getDefinitionRegistry()->loadType(...); */

        $updateSql = $this->getDatabaseUpdateQueryBuilder()->generateEnumReorderQueries();

        self::assertSame(
            [],
            $updateSql,
        );

        $this->applySQL($updateSql);
    }

    protected function getBaseSQL(): array
    {
        return [
            "CREATE TYPE status_type AS ENUM ('started', 'processing', 'finished')"
        ];
    }
}
