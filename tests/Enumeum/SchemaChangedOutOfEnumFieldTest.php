<?php

declare(strict_types=1);

namespace EnumeumTests;

use Doctrine\ORM\Tools\SchemaTool;
use Enumeum\DoctrineEnum\Exception\InvalidArgumentException;
use EnumeumTests\Fixture\AddedValuesStatusType;
use EnumeumTests\Fixture\BaseStatusType;
use EnumeumTests\Fixture\Entity\EntityAdditionalNotEnumField;
use EnumeumTests\Fixture\Entity\EntityAdditionalNotEnumFieldAndEnumAddedValues;
use EnumeumTests\Fixture\Entity\EntityAdditionalNotEnumFieldAndEnumRemovedValues;
use EnumeumTests\Fixture\RemovedValuesStatusType;
use EnumeumTests\Setup\BaseTestCaseSchemaPostgres13;

final class SchemaChangedOutOfEnumFieldTest extends BaseTestCaseSchemaPostgres13
{
    public function testEnumTypeAlreadyExists(): void
    {
        $this->definitionRegistry->loadType(BaseStatusType::class);

        $schemaTool = new SchemaTool($this->em);
        $schema = $this->composeSchema([
            EntityAdditionalNotEnumField::class,
        ]);

        $updateSchemaSql = $schemaTool->getUpdateSchemaSql($schema);

        self::assertEquals(
            [
                "ALTER TABLE entity ALTER field TYPE INT",
                "ALTER TABLE entity ALTER field DROP DEFAULT",
            ],
            $updateSchemaSql,
        );

        /** Here needs manual update of migration SQL with type conversion "USING index::integer".
         * But such manipulation is out of this package's responsibility.
         * Thus, SQL should not be checked.
         */
        // $this->applySQL($updateSchemaSql);
    }

    public function testEnumTypeAlreadyExistsAndNeedsAddValues(): void
    {
        $this->definitionRegistry->loadType(AddedValuesStatusType::class);

        $schemaTool = new SchemaTool($this->em);
        $schema = $this->composeSchema([
            EntityAdditionalNotEnumFieldAndEnumAddedValues::class,
        ]);

        $updateSchemaSql = $schemaTool->getUpdateSchemaSql($schema);

        self::assertEquals(
            [
                "ALTER TABLE entity ALTER field TYPE INT",
                "ALTER TABLE entity ALTER field DROP DEFAULT",
                "ALTER TYPE status_type ADD VALUE IF NOT EXISTS 'accepted'",
                "ALTER TYPE status_type ADD VALUE IF NOT EXISTS 'rejected'",
            ],
            $updateSchemaSql,
        );

        /** Here needs manual update of migration SQL with type conversion "USING index::integer".
         * But such manipulation is out of this package's responsibility.
         * Thus, SQL should not be checked.
         */
        // $this->applySQL($updateSchemaSql);
    }

    public function testEnumTypeAlreadyExistsAndNeedsRemoveValues(): void
    {
        $this->definitionRegistry->loadType(RemovedValuesStatusType::class);

        $schemaTool = new SchemaTool($this->em);
        $schema = $this->composeSchema([
            EntityAdditionalNotEnumFieldAndEnumRemovedValues::class,
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
            "CREATE TABLE entity (id INT NOT NULL, status status_type NOT NULL, field VARCHAR(255) NOT NULL, PRIMARY KEY(id))",
            "COMMENT ON COLUMN entity.status IS 'SOME Comment'",
        ];
    }
}
