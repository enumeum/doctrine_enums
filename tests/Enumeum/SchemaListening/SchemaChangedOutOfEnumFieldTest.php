<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EnumeumTests\SchemaListening;

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
        $this->getDefinitionRegistry()->loadType(BaseStatusType::class);

        $schemaTool = new SchemaTool($this->em);
        $schema = $this->composeSchema([
            EntityAdditionalNotEnumField::class,
        ]);

        $updateSchemaSql = $schemaTool->getUpdateSchemaSql($schema);

        self::assertSame(
            [
                'ALTER TABLE entity ALTER field TYPE INT',
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
        $this->getDefinitionRegistry()->loadType(AddedValuesStatusType::class);

        $schemaTool = new SchemaTool($this->em);
        $schema = $this->composeSchema([
            EntityAdditionalNotEnumFieldAndEnumAddedValues::class,
        ]);

        $updateSchemaSql = $schemaTool->getUpdateSchemaSql($schema);

        self::assertSame(
            [
                'ALTER TABLE entity ALTER field TYPE INT',
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
        $this->getDefinitionRegistry()->loadType(RemovedValuesStatusType::class);

        $schemaTool = new SchemaTool($this->em);
        $schema = $this->composeSchema([
            EntityAdditionalNotEnumFieldAndEnumRemovedValues::class,
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
            "CREATE TYPE status_type AS ENUM ('started', 'processing', 'finished')",
            'CREATE TABLE entity (id INT NOT NULL, status status_type NOT NULL, field VARCHAR(255) NOT NULL, PRIMARY KEY(id))',
            "COMMENT ON COLUMN entity.status IS 'SOME Comment'",
        ];
    }
}
