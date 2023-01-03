<?php

declare(strict_types=1);

namespace Enumeum\DoctrineEnum\Schema;

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
