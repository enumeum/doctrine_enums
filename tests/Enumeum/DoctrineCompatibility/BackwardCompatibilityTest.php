<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EnumeumTests\DoctrineCompatibility;

use Doctrine\ORM\Tools\SchemaTool;
use Enumeum\DoctrineEnum\Schema\Comparator;
use Enumeum\DoctrineEnum\Schema\SchemaManager;
use EnumeumTests\Fixture\Entity\EntityWithNotEnumeumEnum;
use EnumeumTests\Setup\BaseTestCaseSchema;

final class BackwardCompatibilityTest extends BaseTestCaseSchema
{
    public function testOnEnumeumDiffSkipEnumUsedInEntityButNotMarkedAsEnumeumType(): void
    {
        $this->composeSchema([
            EntityWithNotEnumeumEnum::class,
        ]);

        $this->registerTypes([]);

        $manager = new SchemaManager(
            $this->getDefinitionRegistry(),
            $this->getDatabaseDefinitionRegistry($this->em->getConnection()),
            $this->getTableUsageRegistry($this->em->getConnection()),
        );

        $comparator = Comparator::create();
        $diff = $comparator->compareSchemas($manager->createSchemaFromDatabase(), $manager->createSchemaFromConfig());

        $updateSql = $diff->toSql();

        self::assertCount(0, $updateSql);
    }

    public function testOnDoctrineDiffSkipEnumUsedInEntityButNotMarkedAsEnumeumType(): void
    {
        $schema = $this->composeSchema([
            EntityWithNotEnumeumEnum::class,
        ]);

        $this->registerTypes([]);

        $schemaTool = new SchemaTool($this->em);
        $updateSchemaSql = $schemaTool->getUpdateSchemaSql($schema);

        self::assertCount(0, $updateSchemaSql);
    }

    protected function getBaseSQL(): array
    {
        return [
            'CREATE TABLE entity (id INT NOT NULL, status VARCHAR(255) NOT NULL, PRIMARY KEY(id))',
            "COMMENT ON COLUMN entity.status IS 'SOME Comment'",
        ];
    }
}
