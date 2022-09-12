<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine enumerations extension for Postgres" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EnumeumTests;

use Doctrine\ORM\Tools\SchemaTool;
use Enumeum\DoctrineEnum\Exception\InvalidArgumentException;
use Enumeum\DoctrineEnum\Exception\SimultaneousManagementTypeException;
use EnumeumTests\Fixture\AddedValuesStatusType;
use EnumeumTests\Fixture\AnotherEntity\AnotherEntity;
use EnumeumTests\Fixture\AnotherEntity\AnotherEntityEnumAddedValues;
use EnumeumTests\Fixture\AnotherEntity\AnotherEntityEnumRemovedValues;
use EnumeumTests\Fixture\BaseStatusType;
use EnumeumTests\Fixture\Entity\EntityNotEnum;
use EnumeumTests\Fixture\Entity\EntityWithoutTypedField;
use EnumeumTests\Fixture\RemovedValuesStatusType;
use EnumeumTests\Setup\BaseTestCaseSchemaPostgres13;

final class RemoveColumnTest extends BaseTestCaseSchemaPostgres13
{
    public function testEnumTypeNotUsedAnymore(): void
    {
        $this->definitionRegistry->loadType(BaseStatusType::class);

        $schemaTool = new SchemaTool($this->em);
        $schema = $this->composeSchema([
            EntityWithoutTypedField::class,
        ]);

        $updateSchemaSql = $schemaTool->getUpdateSchemaSql($schema);

        self::assertSame(
            [
                'ALTER TABLE entity DROP status',
                'DROP TYPE status_type',
            ],
            $updateSchemaSql,
        );

        $this->applySQL($updateSchemaSql);
    }

    public function testEnumTypeIsNotUsedElsewhereButNeedsAddValues(): void
    {
        $this->definitionRegistry->loadType(AddedValuesStatusType::class);

        $schemaTool = new SchemaTool($this->em);
        $schema = $this->composeSchema([
            EntityWithoutTypedField::class,
        ]);

        $updateSchemaSql = $schemaTool->getUpdateSchemaSql($schema);

        self::assertSame(
            [
                'ALTER TABLE entity DROP status',
                'DROP TYPE status_type',
            ],
            $updateSchemaSql,
        );

        $this->applySQL($updateSchemaSql);
    }

    public function testEnumTypeIsNotUsedElsewhereButNeedsRemoveValues(): void
    {
        $this->definitionRegistry->loadType(RemovedValuesStatusType::class);

        $schemaTool = new SchemaTool($this->em);
        $schema = $this->composeSchema([
            EntityWithoutTypedField::class,
        ]);

        $updateSchemaSql = $schemaTool->getUpdateSchemaSql($schema);

        self::assertSame(
            [
                'ALTER TABLE entity DROP status',
                'DROP TYPE status_type',
            ],
            $updateSchemaSql,
        );

        $this->applySQL($updateSchemaSql);
    }

    public function testEnumTypeIsUsedByAnotherTable(): void
    {
        $this->applySQL([
            'CREATE TABLE another_entity (id INT NOT NULL, status status_type NOT NULL, PRIMARY KEY(id))',
            "COMMENT ON COLUMN another_entity.status IS 'SOME Comment'",
        ]);

        $this->definitionRegistry->loadType(BaseStatusType::class);

        $schemaTool = new SchemaTool($this->em);
        $schema = $this->composeSchema([
            EntityWithoutTypedField::class,
            AnotherEntity::class,
        ]);

        $updateSchemaSql = $schemaTool->getUpdateSchemaSql($schema);

        self::assertSame(
            [
                'ALTER TABLE entity DROP status',
            ],
            $updateSchemaSql,
        );

        $this->applySQL($updateSchemaSql);
    }

    public function testEnumTypeIsUsedByAnotherTableAndNeedsAddValues(): void
    {
        $this->applySQL([
            'CREATE TABLE another_entity (id INT NOT NULL, status status_type NOT NULL, PRIMARY KEY(id))',
            "COMMENT ON COLUMN another_entity.status IS 'SOME Comment'",
        ]);

        $this->definitionRegistry->loadType(AddedValuesStatusType::class);

        $schemaTool = new SchemaTool($this->em);
        $schema = $this->composeSchema([
            EntityWithoutTypedField::class,
            AnotherEntityEnumAddedValues::class,
        ]);

        $updateSchemaSql = $schemaTool->getUpdateSchemaSql($schema);

        self::assertSame(
            [
                'ALTER TABLE entity DROP status',
                "ALTER TYPE status_type ADD VALUE IF NOT EXISTS 'accepted'",
                "ALTER TYPE status_type ADD VALUE IF NOT EXISTS 'rejected'",
            ],
            $updateSchemaSql,
        );

        $this->applySQL($updateSchemaSql);
    }

    public function testEnumTypeIsUsedByAnotherTableAndNeedsRemoveValues(): void
    {
        $this->applySQL([
            'CREATE TABLE another_entity (id INT NOT NULL, status status_type NOT NULL, PRIMARY KEY(id))',
            "COMMENT ON COLUMN another_entity.status IS 'SOME Comment'",
        ]);

        $this->definitionRegistry->loadType(AddedValuesStatusType::class);

        $schemaTool = new SchemaTool($this->em);
        $schema = $this->composeSchema([
            EntityNotEnum::class,
            AnotherEntityEnumRemovedValues::class,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Enum should not be reordered with common Doctrine SchemaTool. Use Enumeum\DoctrineEnum\EnumTool for that.',
        );

        $schemaTool->getUpdateSchemaSql($schema);

        self::fail('Test should not achieve this point.');
    }

    public function testEnumTypeWillBeUsedByAnotherTableDroppingGoesBeforeUsage(): void
    {
        $this->applySQL([
            'CREATE TABLE another_entity (id INT NOT NULL, PRIMARY KEY(id))',
        ]);

        $this->definitionRegistry->loadType(BaseStatusType::class);

        $schemaTool = new SchemaTool($this->em);
        $schema = $this->composeSchema([
            EntityWithoutTypedField::class,
            AnotherEntity::class,
        ]);

        $this->expectException(SimultaneousManagementTypeException::class);
        $this->expectExceptionMessage(
            'Type "status_type" is already queued to be dropped and then attempted to be used. '.
            'SQL generating stopped. '.
            'To avoid this exception change fields and generate migrations consequentially, one by one.',
        );

        $schemaTool->getUpdateSchemaSql($schema);

        self::fail('Test should not achieve this point.');
    }

    public function testEnumTypeWillBeUsedByAnotherTableWithAddingValuesDroppingGoesBeforeUsage(): void
    {
        $this->applySQL([
            'CREATE TABLE another_entity (id INT NOT NULL, PRIMARY KEY(id))',
        ]);

        $this->definitionRegistry->loadType(AddedValuesStatusType::class);

        $schemaTool = new SchemaTool($this->em);
        $schema = $this->composeSchema([
            EntityWithoutTypedField::class,
            AnotherEntityEnumAddedValues::class,
        ]);

        $this->expectException(SimultaneousManagementTypeException::class);
        $this->expectExceptionMessage(
            'Type "status_type" is already queued to be dropped and then attempted to be persisted. '.
            'SQL generating stopped. '.
            'To avoid this exception change fields and generate migrations consequentially, one by one.',
        );

        $schemaTool->getUpdateSchemaSql($schema);

        self::fail('Test should not achieve this point.');
    }

    public function testEnumTypeWillBeUsedByAnotherTableDroppingGoesAfterUsage(): void
    {
        $this->applySQL([
            'CREATE TABLE another_entity (id INT NOT NULL, PRIMARY KEY(id))',
        ]);

        $this->definitionRegistry->loadType(BaseStatusType::class);

        $schemaTool = new SchemaTool($this->em);
        $schema = $this->composeSchema([
            AnotherEntity::class,
            EntityWithoutTypedField::class,
        ]);

        $updateSchemaSql = $schemaTool->getUpdateSchemaSql($schema);

        self::assertSame(
            [
                'ALTER TABLE another_entity ADD status status_type NOT NULL',
                "COMMENT ON COLUMN another_entity.status IS 'SOME Comment'",
                'ALTER TABLE entity DROP status',
            ],
            $updateSchemaSql,
        );

        $this->applySQL($updateSchemaSql);
    }

    public function testEnumTypeWillBeUsedByAnotherTableWithAddingValuesDroppingGoesAfterUsage(): void
    {
        $this->applySQL([
            'CREATE TABLE another_entity (id INT NOT NULL, PRIMARY KEY(id))',
        ]);

        $this->definitionRegistry->loadType(AddedValuesStatusType::class);

        $schemaTool = new SchemaTool($this->em);
        $schema = $this->composeSchema([
            AnotherEntityEnumAddedValues::class,
            EntityWithoutTypedField::class,
        ]);

        $updateSchemaSql = $schemaTool->getUpdateSchemaSql($schema);

        self::assertSame(
            [
                "ALTER TYPE status_type ADD VALUE IF NOT EXISTS 'accepted'",
                "ALTER TYPE status_type ADD VALUE IF NOT EXISTS 'rejected'",
                'ALTER TABLE another_entity ADD status status_type NOT NULL',
                "COMMENT ON COLUMN another_entity.status IS 'SOME Comment'",
                'ALTER TABLE entity DROP status',
            ],
            $updateSchemaSql,
        );

        $this->applySQL($updateSchemaSql);
    }

    protected function getBaseSQL(): array
    {
        return [
            "CREATE TYPE status_type AS ENUM ('started', 'processing', 'finished')",
            'CREATE TABLE entity (id INT NOT NULL, status status_type NOT NULL, PRIMARY KEY(id))',
            "COMMENT ON COLUMN entity.status IS 'SOME Comment'",
        ];
    }
}
