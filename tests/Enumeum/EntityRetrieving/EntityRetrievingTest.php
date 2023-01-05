<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EnumeumTests\EntityRetrieving;

use EnumeumTests\Fixture\BaseStatusType;
use EnumeumTests\Fixture\Entity\Entity;
use EnumeumTests\Setup\BaseTestCaseSchema;

final class EntityRetrievingTest extends BaseTestCaseSchema
{
    public function testEnumTypeUsedByTableWithRecords(): void
    {
        $this->applySQL([
            "CREATE TYPE status_type AS ENUM ('started', 'processing', 'finished')",
            'CREATE TABLE entity (id INT NOT NULL, PRIMARY KEY(id), status status_type NOT NULL)',
            "INSERT INTO entity (id, status) VALUES (1, 'started')",
            "INSERT INTO entity (id, status) VALUES (2, 'processing')",
            "INSERT INTO entity (id, status) VALUES (3, 'finished')",
        ]);

        $this->composeSchema([
            Entity::class
        ]);

        $entity = $this->em->find(Entity::class, 1);

        $this->assertInstanceOf(Entity::class, $entity);
        $this->assertSame(BaseStatusType::STARTED, $entity->getStatus());

        $entity = $this->em->find(Entity::class, 2);

        $this->assertInstanceOf(Entity::class, $entity);
        $this->assertSame(BaseStatusType::PROCESSING, $entity->getStatus());

        $entity = $this->em->find(Entity::class, 3);

        $this->assertInstanceOf(Entity::class, $entity);
        $this->assertSame(BaseStatusType::FINISHED, $entity->getStatus());
    }

    protected function getBaseSQL(): array
    {
        return [];
    }
}
