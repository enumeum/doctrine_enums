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

final class EnumAlterTest extends BaseTestCaseSchema
{
    public function testEnumTypeNotUsedAnywhere(): void
    {
        $this->applySQL([]);

        $diff = new SchemaDiff(
            alterChangeSet: [
                new DefinitionDiff(
                    new Definition('status_type', ['started', 'processing', 'finished']),
                    new Definition('status_type', ['started', 'processing', 'finished', 'accepted', 'rejected']),
                    null,
                ),
            ],
        );

        $updateSql = $diff->toSql();

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

        $diff = new SchemaDiff(
            alterChangeSet: [
                new DefinitionDiff(
                    new Definition('status_type', ['started', 'processing', 'finished']),
                    new Definition('status_type', ['started', 'processing', 'finished', 'accepted', 'rejected']),
                    new Usage('status_type', [
                        new UsageColumn('status_type', 'entity', 'status'),
                    ])
                ),
            ],
        );

        $updateSql = $diff->toSql();

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

        $diff = new SchemaDiff(
            alterChangeSet: [
                new DefinitionDiff(
                    new Definition('status_type', ['started', 'processing', 'finished']),
                    new Definition('status_type', ['started', 'processing', 'finished', 'accepted', 'rejected']),
                    new Usage('status_type', [
                        new UsageColumn('status_type', 'entity', 'status'),
                    ])
                ),
            ],
        );

        $updateSql = $diff->toSql();

        self::assertSame(
            [
                "ALTER TYPE status_type ADD VALUE IF NOT EXISTS 'accepted'",
                "ALTER TYPE status_type ADD VALUE IF NOT EXISTS 'rejected'",
            ],
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
