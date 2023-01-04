<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EnumeumTests\Schema\SchemaDiff;

use Doctrine\DBAL\Exception\DriverException;
use Enumeum\DoctrineEnum\Definition\Definition;
use Enumeum\DoctrineEnum\Schema\SchemaDiff;
use EnumeumTests\Setup\BaseTestCaseSchema;

final class EnumDropTest extends BaseTestCaseSchema
{
    public function testEnumTypeNotUsedAnywhere(): void
    {
        $this->applySQL([]);

        $diff = new SchemaDiff(
            dropChangeSet: [new Definition('status_type', ['started', 'processing', 'finished'])],
        );

        $updateSql = $diff->toSql();

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

        $diff = new SchemaDiff(
            dropChangeSet: [new Definition('status_type', ['started', 'processing', 'finished'])],
        );

        $updateSql = $diff->toSql();

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

    protected function getBaseSQL(): array
    {
        return [
            "CREATE TYPE status_type AS ENUM ('started', 'processing', 'finished')",
        ];
    }
}
