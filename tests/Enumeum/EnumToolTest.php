<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum;

use Enumeum\DoctrineEnum\Definition\Definition;
use Enumeum\DoctrineEnum\EnumTool;
use Enumeum\DoctrineEnum\Schema\SchemaManager;
use EnumeumTests\Fixture\EnumToolType\AlterStatusType;
use EnumeumTests\Fixture\EnumToolType\CreateStatusType;
use EnumeumTests\Fixture\EnumToolType\ReorderStatusType;
use EnumeumTests\Setup\BaseTestCaseSchema;

final class EnumToolTest extends BaseTestCaseSchema
{
    public function testCreateWithMethod(): void
    {
        $tool = EnumTool::create(
            $this->getDefinitionRegistry(),
            $this->em->getConnection(),
        );

        self::assertInstanceOf(EnumTool::class, $tool);
    }

    public function testGetCreateSchemaSql(): void
    {
        $definition = new Definition('status_type', ['started', 'processing', 'finished']);

        $tool = $this->createEnumTool();
        $updateSql = $tool->getCreateSchemaSql([$definition]);

        self::assertSame(
            [
                "CREATE TYPE status_type AS ENUM ('started', 'processing', 'finished')",
            ],
            $updateSql,
        );

        $this->applySQL($updateSql);
    }

    public function testGetDropSchemaSql(): void
    {
        $this->applySQL([
            "CREATE TYPE status_type AS ENUM ('started', 'processing', 'finished')",
        ]);

        $definition = new Definition('status_type', ['started', 'processing', 'finished']);

        $tool = $this->createEnumTool();
        $updateSql = $tool->getDropSchemaSql([$definition]);

        self::assertSame(
            [
                'DROP TYPE IF EXISTS status_type',
            ],
            $updateSql,
        );

        $this->applySQL($updateSql);
    }

    public function testGetUpdateSchemaSql(): void
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

        $tool = $this->createEnumTool();
        $updateSql = $tool->getUpdateSchemaSql();

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

    public function testGetRollbackSchemaSql(): void
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

        $tool = $this->createEnumTool();
        $updateSql = $tool->getRollbackSchemaSql();

        self::assertSame(
            [
                'DROP TYPE IF EXISTS create_status_type',
                'ALTER TYPE alter_status_type RENAME TO alter_status_type__',
                "CREATE TYPE alter_status_type AS ENUM ('started', 'processing', 'finished')",
                'LOCK TABLE entity_one',
                'ALTER TABLE entity_one ALTER COLUMN status TYPE alter_status_type USING status::text::alter_status_type',
                'DROP TYPE IF EXISTS alter_status_type__',

                'ALTER TYPE reorder_status_type RENAME TO reorder_status_type__',
                "CREATE TYPE reorder_status_type AS ENUM ('started', 'processing', 'finished')",
                'LOCK TABLE entity_two',
                'ALTER TABLE entity_two ALTER COLUMN status TYPE reorder_status_type USING status::text::reorder_status_type',
                'DROP TYPE IF EXISTS reorder_status_type__',

                "CREATE TYPE drop_status_type AS ENUM ('started', 'processing', 'finished')",
            ],
            $updateSql,
        );

        $this->applySQLWithinTransaction([
            'DROP TYPE IF EXISTS drop_status_type',
            'ALTER TYPE reorder_status_type RENAME TO reorder_status_type__',
            "CREATE TYPE reorder_status_type AS ENUM ('started', 'finished')",
            'LOCK TABLE entity_two',
            'ALTER TABLE entity_two ALTER COLUMN status TYPE reorder_status_type USING status::text::reorder_status_type',
            'DROP TYPE IF EXISTS reorder_status_type__',
            "ALTER TYPE alter_status_type ADD VALUE IF NOT EXISTS 'accepted'",
            "ALTER TYPE alter_status_type ADD VALUE IF NOT EXISTS 'rejected'",
            "CREATE TYPE create_status_type AS ENUM ('started', 'processing', 'finished')",
        ]);

        $this->applySQLWithinTransaction($updateSql);
    }

    protected function getBaseSQL(): array
    {
        return [];
    }

    private function createEnumTool(): EnumTool
    {
        $manager = new SchemaManager(
            $this->getDefinitionRegistry(),
            $this->getDatabaseDefinitionRegistry($this->em->getConnection()),
            $this->getTableUsageRegistry($this->em->getConnection()),
        );

        return new EnumTool(
            $manager,
            $this->em->getConnection(),
        );
    }
}
