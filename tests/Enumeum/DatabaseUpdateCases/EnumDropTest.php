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

final class EnumDropTest extends BaseTestCaseSchemaPostgres13
{
    public function testEnumTypeNotUsedAnywhere(): void
    {
        /** There is no any loaded type. */
        /** $this->getDefinitionRegistry()->loadType(...); */
        $updateSql = $this->getDatabaseUpdateQueryBuilder()->generateEnumDropQueries();

        self::assertSame(
            [
                'DROP TYPE IF EXISTS status_type',
            ],
            $updateSql,
        );

        $this->applySQL($updateSql);
    }

    /**
     * TODO: Probably need to add check of impossible type dropping
     */
    public function testEnumTypeUsedByTable(): void
    {
        $this->applySQL([
            'CREATE TABLE entity (id INT NOT NULL, PRIMARY KEY(id), status status_type NOT NULL)',
        ]);

        /** There is no any loaded type. */
        /** $this->getDefinitionRegistry()->loadType(...); */
        $updateSql = $this->getDatabaseUpdateQueryBuilder()->generateEnumDropQueries();

        self::assertSame(
            [
                'DROP TYPE IF EXISTS status_type',
            ],
            $updateSql,
        );

        $this->expectException(DriverException::class);
        $this->expectExceptionMessageMatches(
            '~.*cannot drop type status_type because other objects depend on it.*~',
        );

        $this->applySQL($updateSql);
    }

    public function testEnumTypeCreating(): void
    {
        $this->applySQL([
            'DROP TYPE IF EXISTS status_type',
        ]);

        $this->getDefinitionRegistry()->loadType(BaseStatusType::class);

        $updateSql = $this->getDatabaseUpdateQueryBuilder()->generateEnumDropQueries();

        self::assertSame(
            [],
            $updateSql,
        );

        $this->applySQL($updateSql);
    }

    public function testEnumTypeAltering(): void
    {
        $this->getDefinitionRegistry()->loadType(AddedValuesStatusType::class);

        $updateSql = $this->getDatabaseUpdateQueryBuilder()->generateEnumDropQueries();

        self::assertSame(
            [],
            $updateSql,
        );

        $this->applySQL($updateSql);
    }

    public function testEnumTypeReordering(): void
    {
        $this->getDefinitionRegistry()->loadType(RemovedValuesStatusType::class);

        $updateSql = $this->getDatabaseUpdateQueryBuilder()->generateEnumDropQueries();

        self::assertSame(
            [],
            $updateSql,
        );

        $this->applySQL($updateSql);
    }

    protected function getBaseSQL(): array
    {
        return [
            "CREATE TYPE status_type AS ENUM ('started', 'processing', 'finished')",
        ];
    }
}
