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
    public function testEnumTypeNotExists(): void
    {
        $this->applySQL([
            "CREATE TYPE alter_status_type AS ENUM ('started', 'processing', 'finished')",
            "CREATE TYPE reorder_status_type AS ENUM ('started', 'processing', 'finished')",
            "CREATE TYPE drop_status_type AS ENUM ('started', 'processing', 'finished')",

            'CREATE TABLE entity_one (id INT NOT NULL, PRIMARY KEY(id), status alter_status_type NOT NULL)',
            'CREATE TABLE entity_two (id INT NOT NULL, PRIMARY KEY(id), status reorder_status_type NOT NULL)',
        ]);

        $this->getDefinitionRegistry()->loadType(CreateStatusType::class);
        $this->getDefinitionRegistry()->loadType(AlterStatusType::class);
        $this->getDefinitionRegistry()->loadType(ReorderStatusType::class);

        $manager = $this->createSchemaManager();

        $comparator = Comparator::create();
        $diff = $comparator->compareSchemas($manager->createSchemaFromDatabase(), $manager->createSchemaFromConfig());

        $updateSql = $diff->toSql();

        self::assertSame(
            [
                'DROP TYPE IF EXISTS drop_status_type',
                'ALTER TYPE reorder_status_type RENAME TO reorder_status_type__',
                "CREATE TYPE reorder_status_type AS ENUM ('started', 'finished')",
                'LOCK TABLE entity_two',
                'ALTER TABLE entity_two ALTER COLUMN status TYPE reorder_status_type USING status::text::reorder_status_type',
                'DROP TYPE IF EXISTS reorder_status_type__',
                "ALTER TYPE alter_status_type ADD VALUE IF NOT EXISTS 'accepted'",
                "ALTER TYPE alter_status_type ADD VALUE IF NOT EXISTS 'rejected'",
                "CREATE TYPE create_status_type AS ENUM ('started', 'processing', 'finished')",
            ],
            $updateSql,
        );

        $this->applySQLWithinTransaction($updateSql);
    }

    protected function createSchemaManager(): SchemaManager
    {
        return new SchemaManager(
            $this->getDefinitionRegistry(),
            $this->getDatabaseDefinitionRegistry($this->em->getConnection()),
            $this->getTableUsageRegistry($this->em->getConnection()),
        );
    }

    protected function getBaseSQL(): array
    {
        return [];
    }
}
