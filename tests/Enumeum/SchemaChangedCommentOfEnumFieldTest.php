<?php

declare(strict_types=1);

namespace EnumeumTests;

use Doctrine\ORM\Tools\SchemaTool;
use Enumeum\DoctrineEnum\Exception\InvalidArgumentException;
use EnumeumTests\Fixture\AddedValuesStatusType;
use EnumeumTests\Fixture\BaseStatusType;
use EnumeumTests\Fixture\Entity\EntityChangedComment;
use EnumeumTests\Fixture\Entity\EntityChangedCommentAndEnumAddedValues;
use EnumeumTests\Fixture\Entity\EntityChangedCommentAndEnumRemovedValues;
use EnumeumTests\Fixture\RemovedValuesStatusType;
use EnumeumTests\Setup\BaseTestCaseSchemaPostgres13;

final class SchemaChangedCommentOfEnumFieldTest extends BaseTestCaseSchemaPostgres13
{
    public function testEnumTypeAlreadyExists(): void
    {
        $this->definitionRegistry->loadType(BaseStatusType::class);

        $schemaTool = new SchemaTool($this->em);
        $schema = $this->composeSchema([
            EntityChangedComment::class,
        ]);

        $updateSchemaSql = $schemaTool->getUpdateSchemaSql($schema);

        self::assertEquals(
            [
                "COMMENT ON COLUMN entity.status IS 'CHANGED Comment'",
            ],
            $updateSchemaSql,
        );

        $this->applySQL($updateSchemaSql);
    }

    public function testEnumTypeAlreadyExistsAndNeedsAddValues(): void
    {
        $this->definitionRegistry->loadType(AddedValuesStatusType::class);

        $schemaTool = new SchemaTool($this->em);
        $schema = $this->composeSchema([
            EntityChangedCommentAndEnumAddedValues::class,
        ]);

        $updateSchemaSql = $schemaTool->getUpdateSchemaSql($schema);

        self::assertEquals(
            [
                "ALTER TYPE status_type ADD VALUE IF NOT EXISTS 'accepted'",
                "ALTER TYPE status_type ADD VALUE IF NOT EXISTS 'rejected'",
                "COMMENT ON COLUMN entity.status IS 'CHANGED Comment'",
            ],
            $updateSchemaSql,
        );

        $this->applySQL($updateSchemaSql);
    }

    public function testEnumTypeAlreadyExistsAndNeedsRemoveValues(): void
    {
        $this->definitionRegistry->loadType(RemovedValuesStatusType::class);

        $schemaTool = new SchemaTool($this->em);
        $schema = $this->composeSchema([
            EntityChangedCommentAndEnumRemovedValues::class,
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
