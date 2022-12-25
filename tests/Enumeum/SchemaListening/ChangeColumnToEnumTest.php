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
use EnumeumTests\Fixture\BaseStatusType;
use EnumeumTests\Fixture\Entity\Entity;
use EnumeumTests\Setup\BaseTestCaseSchema;

final class ChangeColumnToEnumTest extends BaseTestCaseSchema
{
    public function test(): void
    {
        $this->registerTypes([BaseStatusType::class]);

        $schemaTool = new SchemaTool($this->em);
        $schema = $this->composeSchema([
            Entity::class,
        ]);

        $updateSchemaSql = $schemaTool->getUpdateSchemaSql($schema);

        self::assertSame(
            [
                'ALTER TABLE entity ALTER status TYPE status_type USING status::text::status_type',
            ],
            $updateSchemaSql,
        );

        $this->applySQL($updateSchemaSql);
    }

    protected function getBaseSQL(): array
    {
        return [
            "CREATE TYPE status_type AS ENUM ('started', 'processing', 'finished')",
            'CREATE TABLE entity (id INT NOT NULL, status VARCHAR(255) NOT NULL, PRIMARY KEY(id))',
            "COMMENT ON COLUMN entity.status IS 'SOME Comment'",
        ];
    }
}
