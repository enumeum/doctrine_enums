<?php

declare(strict_types=1);

namespace EnumeumTests;

use Doctrine\ORM\Tools\SchemaTool;
use Enumeum\DoctrineEnum\Exception\InvalidArgumentException;
use EnumeumTests\Fixture\AddedValuesStatusType;
use EnumeumTests\Fixture\AnotherEntity\AnotherEntity;
use EnumeumTests\Fixture\AnotherEntity\AnotherEntityEnumAddedValues;
use EnumeumTests\Fixture\AnotherEntity\AnotherEntityEnumRemovedValues;
use EnumeumTests\Fixture\BaseStatusType;
use EnumeumTests\Fixture\Entity\EntityNotEnum;
use EnumeumTests\Fixture\RemovedValuesStatusType;
use EnumeumTests\Setup\BaseTestCaseSchemaPostgres13;

final class ChangeColumnFromEnumTest extends BaseTestCaseSchemaPostgres13
{
    public function testEnumTypeIsNotUsedElsewhere(): void
    {
        $this->definitionRegistry->loadType(BaseStatusType::class);

        $schemaTool = new SchemaTool($this->em);
        $schema = $this->composeSchema([
            EntityNotEnum::class,
        ]);

        $updateSchemaSql = $schemaTool->getUpdateSchemaSql($schema);

        self::assertEquals(
            [
                "ALTER TABLE entity ALTER status TYPE VARCHAR(255)",
                "ALTER TABLE entity ALTER status DROP DEFAULT",
                "DROP TYPE status_type",
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
            EntityNotEnum::class,
        ]);

        $updateSchemaSql = $schemaTool->getUpdateSchemaSql($schema);

        self::assertEquals(
            [
                "ALTER TABLE entity ALTER status TYPE VARCHAR(255)",
                "ALTER TABLE entity ALTER status DROP DEFAULT",
                "DROP TYPE status_type",
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
            EntityNotEnum::class,
        ]);

        $updateSchemaSql = $schemaTool->getUpdateSchemaSql($schema);

        self::assertEquals(
            [
                "ALTER TABLE entity ALTER status TYPE VARCHAR(255)",
                "ALTER TABLE entity ALTER status DROP DEFAULT",
                "DROP TYPE status_type",
            ],
            $updateSchemaSql,
        );

        $this->applySQL($updateSchemaSql);
    }

    public function testEnumTypeIsUsedByAnotherTable(): void
    {
        $this->applySQL([
            "CREATE TABLE another_entity (id INT NOT NULL, status status_type NOT NULL, PRIMARY KEY(id))",
            "COMMENT ON COLUMN another_entity.status IS 'SOME Comment'",
        ]);

        $this->definitionRegistry->loadType(BaseStatusType::class);

        $schemaTool = new SchemaTool($this->em);
        $schema = $this->composeSchema([
            EntityNotEnum::class,
            AnotherEntity::class,
        ]);

        $updateSchemaSql = $schemaTool->getUpdateSchemaSql($schema);

        self::assertEquals(
            [
                "ALTER TABLE entity ALTER status TYPE VARCHAR(255)",
                "ALTER TABLE entity ALTER status DROP DEFAULT",
            ],
            $updateSchemaSql,
        );

        $this->applySQL($updateSchemaSql);
    }

    public function testEnumTypeIsUsedByAnotherTableAndNeedsAddValues(): void
    {
        $this->applySQL([
            "CREATE TABLE another_entity (id INT NOT NULL, status status_type NOT NULL, PRIMARY KEY(id))",
            "COMMENT ON COLUMN another_entity.status IS 'SOME Comment'",
        ]);

        $this->definitionRegistry->loadType(AddedValuesStatusType::class);

        $schemaTool = new SchemaTool($this->em);
        $schema = $this->composeSchema([
            EntityNotEnum::class,
            AnotherEntityEnumAddedValues::class,
        ]);

        $updateSchemaSql = $schemaTool->getUpdateSchemaSql($schema);

        self::assertEquals(
            [
                "ALTER TABLE entity ALTER status TYPE VARCHAR(255)",
                "ALTER TABLE entity ALTER status DROP DEFAULT",
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
            "CREATE TABLE another_entity (id INT NOT NULL, status status_type NOT NULL, PRIMARY KEY(id))",
            "COMMENT ON COLUMN another_entity.status IS 'SOME Comment'",
        ]);

        $this->definitionRegistry->loadType(RemovedValuesStatusType::class);

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

        $this->fail('Test should not achieve this point.');
    }

    protected function getBaseSQL(): array
    {
        return [
            "CREATE TYPE status_type AS ENUM ('started', 'processing', 'finished')",
            "CREATE TABLE entity (id INT NOT NULL, status status_type NOT NULL, PRIMARY KEY(id))",
            "COMMENT ON COLUMN entity.status IS 'SOME Comment'",
        ];
    }
}