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
use Enumeum\DoctrineEnum\Schema\SchemaDiff;
use EnumeumTests\Setup\BaseTestCaseSchema;

final class EnumCreateTest extends BaseTestCaseSchema
{
    public function testEnumTypeNotExists(): void
    {
        $diff = new SchemaDiff(
            createChangeSet: [new Definition('status_type', ['started', 'processing', 'finished'])],
        );

        $updateSql = $diff->toSql();

        self::assertSame(
            [
                "CREATE TYPE status_type AS ENUM ('started', 'processing', 'finished')",
            ],
            $updateSql,
        );

        $this->applySQL($updateSql);
    }

    public function testEnumTypeNotExistsWithoutCreating(): void
    {
        $diff = new SchemaDiff(
            createChangeSet: [new Definition('status_type', ['started', 'processing', 'finished'])],
        );

        $updateSql = $diff->toSql(withoutCreating: true);

        self::assertCount(0, $updateSql);
    }

    protected function getBaseSQL(): array
    {
        return [];
    }
}
