<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EnumeumTests\Schema\SchemaDiff;

use Enumeum\DoctrineEnum\Definition\Definition;
use Enumeum\DoctrineEnum\EnumUsage\Usage;
use Enumeum\DoctrineEnum\EnumUsage\UsageColumn;
use Enumeum\DoctrineEnum\Schema\DefinitionDiff;
use Enumeum\DoctrineEnum\Schema\SchemaDiff;
use EnumeumTests\Setup\BaseTestCaseSchema;

final class ManyEnumChangedTest extends BaseTestCaseSchema
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

        $diff = new SchemaDiff(
            createChangeSet: [new Definition('create_status_type', ['started', 'processing', 'finished'])],
            alterChangeSet: [
                new DefinitionDiff(
                    new Definition('alter_status_type', ['started', 'processing', 'finished']),
                    new Definition('alter_status_type', ['started', 'processing', 'finished', 'accepted', 'rejected']),
                    new Usage('alter_status_type', [
                        new UsageColumn('alter_status_type', 'entity_one', 'status'),
                    ])
                ),
                new DefinitionDiff(
                    new Definition('reorder_status_type', ['started', 'processing', 'finished']),
                    new Definition('reorder_status_type', ['started', 'finished']),
                    new Usage('reorder_status_type', [
                        new UsageColumn('reorder_status_type', 'entity_two', 'status'),
                    ])
                ),
            ],
            dropChangeSet: [new Definition('drop_status_type', ['started', 'processing', 'finished'])],
        );

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
}
