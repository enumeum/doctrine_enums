<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EnumeumTests\Schema;

use Enumeum\DoctrineEnum\Schema\Comparator;
use Enumeum\DoctrineEnum\Schema\SchemaManager;
use EnumeumTests\Fixture\EnumToolType\AlterStatusType;
use EnumeumTests\Fixture\EnumToolType\CreateStatusType;
use EnumeumTests\Fixture\EnumToolType\ReorderStatusType;
use EnumeumTests\Setup\BaseTestCaseSchema;

final class ComparatorTest extends BaseTestCaseSchema
{
    public function testCreateEnums(): void
    {
        $this->registerTypes([
            CreateStatusType::class,
        ]);

        $manager = $this->createSchemaManager();

        $comparator = new Comparator();
        $diff = $comparator->compareSchemas($manager->createSchemaFromDatabase(), $manager->createSchemaFromConfig());

        $updateSql = $diff->toSql();

        self::assertSame(
            [
                "CREATE TYPE create_status_type AS ENUM ('started', 'processing', 'finished')",
            ],
            $updateSql,
        );

        $this->applySQLWithinTransaction($updateSql);
    }

    public function testAlterEnums(): void
    {
        $this->applySQL([
            "CREATE TYPE alter_status_type AS ENUM ('started', 'processing', 'finished')",
            'CREATE TABLE entity_one (id INT NOT NULL, PRIMARY KEY(id), status alter_status_type NOT NULL)',
        ]);

        $this->registerTypes([
            AlterStatusType::class,
        ]);

        $manager = $this->createSchemaManager();

        $comparator = new Comparator();
        $diff = $comparator->compareSchemas($manager->createSchemaFromDatabase(), $manager->createSchemaFromConfig());

        $updateSql = $diff->toSql();

        self::assertSame(
            [
                "ALTER TYPE alter_status_type ADD VALUE IF NOT EXISTS 'accepted'",
                "ALTER TYPE alter_status_type ADD VALUE IF NOT EXISTS 'rejected'",
            ],
            $updateSql,
        );

        $this->applySQLWithinTransaction($updateSql);
    }

    public function testReorderEnums(): void
    {
        $this->applySQL([
            "CREATE TYPE reorder_status_type AS ENUM ('started', 'processing', 'finished')",
            'CREATE TABLE entity_two (id INT NOT NULL, PRIMARY KEY(id), status reorder_status_type NOT NULL)',
        ]);

        $this->registerTypes([
            ReorderStatusType::class,
        ]);

        $manager = $this->createSchemaManager();

        $comparator = new Comparator();
        $diff = $comparator->compareSchemas($manager->createSchemaFromDatabase(), $manager->createSchemaFromConfig());

        $updateSql = $diff->toSql();

        self::assertSame(
            [
                'ALTER TYPE reorder_status_type RENAME TO reorder_status_type__',
                "CREATE TYPE reorder_status_type AS ENUM ('started', 'finished')",
                'LOCK TABLE entity_two',
                'ALTER TABLE entity_two ALTER COLUMN status TYPE reorder_status_type USING status::text::reorder_status_type',
                'DROP TYPE IF EXISTS reorder_status_type__',
            ],
            $updateSql,
        );

        $this->applySQLWithinTransaction($updateSql);
    }

    public function testDropEnums(): void
    {
        $this->applySQL([
            "CREATE TYPE drop_status_type AS ENUM ('started', 'processing', 'finished')",
        ]);

        $this->registerTypes([
        ]);

        $manager = $this->createSchemaManager();

        $comparator = new Comparator();
        $diff = $comparator->compareSchemas($manager->createSchemaFromDatabase(), $manager->createSchemaFromConfig());

        $updateSql = $diff->toSql();

        self::assertSame(
            [
                'DROP TYPE IF EXISTS drop_status_type',
            ],
            $updateSql,
        );

        $this->applySQLWithinTransaction($updateSql);
    }

    public function testMixedEnumsChanged(): void
    {
        $this->applySQL([
            "CREATE TYPE alter_status_type AS ENUM ('started', 'processing', 'finished')",
            "CREATE TYPE reorder_status_type AS ENUM ('started', 'processing', 'finished')",
            "CREATE TYPE drop_status_type AS ENUM ('started', 'processing', 'finished')",

            'CREATE TABLE entity_one (id INT NOT NULL, PRIMARY KEY(id), status alter_status_type NOT NULL)',
            'CREATE TABLE entity_two (id INT NOT NULL, PRIMARY KEY(id), status reorder_status_type NOT NULL)',
        ]);

        $this->registerTypes([
            CreateStatusType::class,
            AlterStatusType::class,
            ReorderStatusType::class,
        ]);

        $manager = $this->createSchemaManager();

        $comparator = new Comparator();
        $diff = $comparator->compareSchemas($manager->createSchemaFromDatabase(), $manager->createSchemaFromConfig());

        $updateSql = $diff->toSql();

        self::assertSame(
            [
                'DROP TYPE IF EXISTS drop_status_type',
                "ALTER TYPE alter_status_type ADD VALUE IF NOT EXISTS 'accepted'",
                "ALTER TYPE alter_status_type ADD VALUE IF NOT EXISTS 'rejected'",
                'ALTER TYPE reorder_status_type RENAME TO reorder_status_type__',
                "CREATE TYPE reorder_status_type AS ENUM ('started', 'finished')",
                'LOCK TABLE entity_two',
                'ALTER TABLE entity_two ALTER COLUMN status TYPE reorder_status_type USING status::text::reorder_status_type',
                'DROP TYPE IF EXISTS reorder_status_type__',
                "CREATE TYPE create_status_type AS ENUM ('started', 'processing', 'finished')",
            ],
            $updateSql,
        );

        $this->applySQLWithinTransaction($updateSql);
    }

    protected function getBaseSQL(): array
    {
        return [];
    }

    private function createSchemaManager(): SchemaManager
    {
        return new SchemaManager(
            $this->getDefinitionRegistry(),
            $this->getDatabaseDefinitionRegistry($this->em->getConnection()),
            $this->getTableUsageRegistry($this->em->getConnection()),
        );
    }
}
