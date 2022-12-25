<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EnumeumTests\SchemaListeningDeprecated;

use Doctrine\ORM\Tools\SchemaTool;
use Enumeum\DoctrineEnum\Exception\InvalidArgumentException;
use EnumeumTests\Fixture\AddedValuesStatusType;
use EnumeumTests\Fixture\BaseStatusType;
use EnumeumTests\Fixture\Entity\Entity;
use EnumeumTests\Fixture\Entity\EntityEnumAddedValues;
use EnumeumTests\Fixture\Entity\EntityEnumRemovedValues;
use EnumeumTests\Fixture\RemovedValuesStatusType;
use EnumeumTests\Setup\BaseTestCaseSchemaDeprecated;

final class ChangeColumnToEnumTest extends BaseTestCaseSchemaDeprecated
{
    public function testEnumTypeNotExists(): void
    {
        $this->getDefinitionRegistry()->loadType(BaseStatusType::class);

        $schemaTool = new SchemaTool($this->em);
        $schema = $this->composeSchema([
            Entity::class,
        ]);

        $updateSchemaSql = $schemaTool->getUpdateSchemaSql($schema);

        self::assertSame(
            [
                "CREATE TYPE status_type AS ENUM ('started', 'processing', 'finished')",
                'ALTER TABLE entity ALTER status TYPE status_type USING status::status_type',
            ],
            $updateSchemaSql,
        );

        $this->applySQL($updateSchemaSql);
    }

    public function testEnumTypeAlreadyExists(): void
    {
        $this->applySQL([
            "CREATE TYPE status_type AS ENUM ('started', 'processing', 'finished')",
        ]);

        $this->getDefinitionRegistry()->loadType(BaseStatusType::class);

        $schemaTool = new SchemaTool($this->em);
        $schema = $this->composeSchema([
            Entity::class,
        ]);

        $updateSchemaSql = $schemaTool->getUpdateSchemaSql($schema);

        self::assertSame(
            [
                'ALTER TABLE entity ALTER status TYPE status_type USING status::status_type',
            ],
            $updateSchemaSql,
        );

        $this->applySQL($updateSchemaSql);
    }

    public function testEnumTypeAlreadyExistsAndNeedsAddValues(): void
    {
        $this->applySQL([
            "CREATE TYPE status_type AS ENUM ('started', 'processing', 'finished')",
        ]);

        $this->getDefinitionRegistry()->loadType(AddedValuesStatusType::class);

        $schemaTool = new SchemaTool($this->em);
        $schema = $this->composeSchema([
            EntityEnumAddedValues::class,
        ]);

        $updateSchemaSql = $schemaTool->getUpdateSchemaSql($schema);

        self::assertSame(
            [
                "ALTER TYPE status_type ADD VALUE IF NOT EXISTS 'accepted'",
                "ALTER TYPE status_type ADD VALUE IF NOT EXISTS 'rejected'",
                'ALTER TABLE entity ALTER status TYPE status_type USING status::status_type',
            ],
            $updateSchemaSql,
        );

        $this->applySQL($updateSchemaSql);
    }

    public function testEnumTypeAlreadyExistsAndNeedsRemoveValues(): void
    {
        $this->applySQL([
            "CREATE TYPE status_type AS ENUM ('started', 'processing', 'finished')",
        ]);

        $this->getDefinitionRegistry()->loadType(RemovedValuesStatusType::class);

        $schemaTool = new SchemaTool($this->em);
        $schema = $this->composeSchema([
            EntityEnumRemovedValues::class,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Enum should not be reordered with common Doctrine SchemaTool. Use Enumeum\DoctrineEnum\EnumTool for that.',
        );

        $schemaTool->getUpdateSchemaSql($schema);

        self::fail('Test should not achieve this point.');
    }

    protected function getBaseSQL(): array
    {
        return [
            'CREATE TABLE entity (id INT NOT NULL, status VARCHAR(255) NOT NULL, PRIMARY KEY(id))',
            "COMMENT ON COLUMN entity.status IS 'SOME Comment'",
        ];
    }
}
