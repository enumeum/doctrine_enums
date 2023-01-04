<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum\DoctrineEnum\Schema;

use Doctrine\DBAL\Connection;
use Enumeum\DoctrineEnum\Definition\DatabaseDefinitionRegistry;
use Enumeum\DoctrineEnum\Definition\Definition;
use Enumeum\DoctrineEnum\Definition\DefinitionRegistry;
use Enumeum\DoctrineEnum\EnumUsage\TableUsageRegistry;

class SchemaManager
{
    public function __construct(
        private readonly DefinitionRegistry $registry,
        private readonly DatabaseDefinitionRegistry $databaseRegistry,
        private readonly TableUsageRegistry $usageRegistry,
    ) {
    }

    public static function create(DefinitionRegistry $registry, Connection $connection): self
    {
        return new self(
            $registry,
            new DatabaseDefinitionRegistry($connection),
            new TableUsageRegistry($connection),
        );
    }

    public function createSchema(iterable $definitions, iterable $usages = []): Schema
    {
        return new Schema($definitions, $usages);
    }

    /**
     * @param iterable<Definition> $definitions
     */
    public function createSchemaFromDefinitions(iterable $definitions): Schema
    {
        return $this->createSchema(
            $definitions,
            $this->usageRegistry->getUsages(),
        );
    }

    public function createSchemaFromConfig(): Schema
    {
        return $this->createSchema(
            $this->registry->getDefinitions(),
            $this->usageRegistry->getUsages(),
        );
    }

    public function createSchemaFromDatabase(): Schema
    {
        return $this->createSchema(
            $this->databaseRegistry->getDefinitions(),
            $this->usageRegistry->getUsages(),
        );
    }
}
