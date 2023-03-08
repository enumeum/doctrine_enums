<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EnumeumTests\DoctrineCompatibility;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Enumeum\DoctrineEnum\Type\EnumeumType;
use EnumeumTests\Fixture\AnotherStatusType;
use EnumeumTests\Fixture\BaseStatusType;
use EnumeumTests\Setup\BaseTestCaseSchema;

final class SchemaManagerTest extends BaseTestCaseSchema
{
    public function testIntrospectSchemaWithTypesInDatabase(): void
    {
        $this->applySQL([
            "CREATE TYPE status_type AS ENUM ('started', 'processing', 'finished')",
            'CREATE TABLE entity (id INT NOT NULL, status status_type NOT NULL, PRIMARY KEY(id))',
            "COMMENT ON COLUMN entity.status IS 'SOME Comment'",
        ]);

        $this->registerTypes([
            BaseStatusType::class,
            AnotherStatusType::class,
        ]);

        $connection = $this->em->getConnection();
        $schemaManager = $connection->createSchemaManager();
        $schema = $schemaManager->introspectSchema();

        $statusColumnType = $schema->getTable('entity')->getColumn('status')->getType();

        self::assertInstanceOf(EnumeumType::class, $statusColumnType);
        self::assertSame('status_type', $statusColumnType->getName());
        self::assertSame(['status_type'], $statusColumnType->getMappedDatabaseTypes(new PostgreSQLPlatform()));
    }

    protected function getBaseSQL(): array
    {
        return [];
    }
}
