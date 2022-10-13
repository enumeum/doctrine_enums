<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine enumerations extension for Postgres" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EnumeumTests\DatabaseUpdateCases;

use EnumeumTests\Fixture\AddedValuesStatusType;
use EnumeumTests\Fixture\RemovedValuesStatusType;
use EnumeumTests\Setup\BaseTestCaseSchemaPostgres13;

final class EnumAlterTest extends BaseTestCaseSchemaPostgres13
{
    public function testEnumTypeNotUsedAnywhere(): void
    {
        $this->getDefinitionRegistry()->loadType(AddedValuesStatusType::class);

        $updateSql = $this->getDatabaseUpdateQueryBuilder()->generateEnumAlterQueries();

        self::assertSame(
            [
                "ALTER TYPE status_type ADD VALUE IF NOT EXISTS 'accepted'",
                "ALTER TYPE status_type ADD VALUE IF NOT EXISTS 'rejected'",
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

        $this->getDefinitionRegistry()->loadType(AddedValuesStatusType::class);

        $updateSql = $this->getDatabaseUpdateQueryBuilder()->generateEnumAlterQueries();

        self::assertSame(
            [
                "ALTER TYPE status_type ADD VALUE IF NOT EXISTS 'accepted'",
                "ALTER TYPE status_type ADD VALUE IF NOT EXISTS 'rejected'",
            ],
            $updateSql,
        );

        $this->applySQL($updateSql);
    }

    public function testEnumTypeUsedByTableWithRecords(): void
    {
        $this->applySQL([
            "CREATE TABLE entity (id INT NOT NULL, PRIMARY KEY(id), status status_type NOT NULL)",
            "INSERT INTO entity (id, status) VALUES (1, 'started')",
            "INSERT INTO entity (id, status) VALUES (2, 'processing')",
            "INSERT INTO entity (id, status) VALUES (3, 'finished')",
        ]);

        $this->getDefinitionRegistry()->loadType(AddedValuesStatusType::class);

        $updateSql = $this->getDatabaseUpdateQueryBuilder()->generateEnumAlterQueries();

        self::assertSame(
            [
                "ALTER TYPE status_type ADD VALUE IF NOT EXISTS 'accepted'",
                "ALTER TYPE status_type ADD VALUE IF NOT EXISTS 'rejected'",
            ],
            $updateSql,
        );

        $this->applySQL($updateSql);
    }

    public function testEnumTypeCreating(): void
    {
        $this->applySQL([
            "DROP TYPE status_type",
        ]);

        $this->getDefinitionRegistry()->loadType(AddedValuesStatusType::class);

        $updateSql = $this->getDatabaseUpdateQueryBuilder()->generateEnumAlterQueries();

        self::assertSame(
            [],
            $updateSql,
        );

        $this->applySQL($updateSql);
    }

    public function testEnumTypeReordering(): void
    {
        $this->getDefinitionRegistry()->loadType(RemovedValuesStatusType::class);

        $updateSql = $this->getDatabaseUpdateQueryBuilder()->generateEnumAlterQueries();

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

        $updateSql = $this->getDatabaseUpdateQueryBuilder()->generateEnumAlterQueries();

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
