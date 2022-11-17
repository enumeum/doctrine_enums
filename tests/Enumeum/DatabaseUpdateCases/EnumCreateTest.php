<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EnumeumTests\DatabaseUpdateCases;

use EnumeumTests\Fixture\AddedValuesStatusType;
use EnumeumTests\Fixture\BaseStatusType;
use EnumeumTests\Fixture\RemovedValuesStatusType;
use EnumeumTests\Setup\BaseTestCaseSchemaPostgres13;

final class EnumCreateTest extends BaseTestCaseSchemaPostgres13
{
    public function testEnumTypeNotExists(): void
    {
        $this->getDefinitionRegistry()->loadType(BaseStatusType::class);

        $updateSql = $this->getDatabaseUpdateQueryBuilder()->generateEnumCreateQueries();

        self::assertSame(
            [
                "CREATE TYPE status_type AS ENUM ('started', 'processing', 'finished')",
            ],
            $updateSql,
        );

        $this->applySQL($updateSql);
    }

    public function testEnumTypeAlreadyExists(): void
    {
        $this->applySQL([
            "CREATE TYPE status_type AS ENUM ('started', 'processing', 'finished')",
        ]);

        $this->getDefinitionRegistry()->loadType(BaseStatusType::class);

        $updateSql = $this->getDatabaseUpdateQueryBuilder()->generateEnumCreateQueries();

        self::assertSame(
            [],
            $updateSql,
        );

        $this->applySQL($updateSql);
    }

    public function testEnumTypeAltering(): void
    {
        $this->applySQL([
            "CREATE TYPE status_type AS ENUM ('started', 'processing', 'finished')",
        ]);

        $this->getDefinitionRegistry()->loadType(AddedValuesStatusType::class);

        $updateSql = $this->getDatabaseUpdateQueryBuilder()->generateEnumCreateQueries();

        self::assertSame(
            [],
            $updateSql,
        );

        $this->applySQL($updateSql);
    }

    public function testEnumTypeReordering(): void
    {
        $this->applySQL([
            "CREATE TYPE status_type AS ENUM ('started', 'processing', 'finished')",
        ]);

        $this->getDefinitionRegistry()->loadType(RemovedValuesStatusType::class);

        $updateSql = $this->getDatabaseUpdateQueryBuilder()->generateEnumCreateQueries();

        self::assertSame(
            [],
            $updateSql,
        );

        $this->applySQL($updateSql);
    }

    public function testEnumTypeDropping(): void
    {
        $this->applySQL([
            "CREATE TYPE status_type AS ENUM ('started', 'processing', 'finished')",
        ]);

        /** There is no any loaded type. */
        /** $this->getDefinitionRegistry()->loadType(...); */
        $updateSql = $this->getDatabaseUpdateQueryBuilder()->generateEnumCreateQueries();

        self::assertSame(
            [],
            $updateSql,
        );

        $this->applySQL($updateSql);
    }

    protected function getBaseSQL(): array
    {
        return [];
    }
}
