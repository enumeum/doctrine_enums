<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum\DoctrineEnum;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\Tools\ToolsException;
use Enumeum\DoctrineEnum\Definition\Definition;
use Enumeum\DoctrineEnum\Definition\DefinitionRegistry;
use Enumeum\DoctrineEnum\Schema\Comparator;
use Enumeum\DoctrineEnum\Schema\SchemaManager;
use Throwable;

class EnumTool
{
    public function __construct(
        private readonly SchemaManager $manager,
        private readonly Connection $connection,
        private readonly bool $ignoreUnknownDatabaseTypes = false,
        private readonly Comparator $comparator = new Comparator(),
    ) {
    }

    public static function create(
        DefinitionRegistry $registry,
        Connection $connection,
        bool $ignoreUnknownDatabaseTypes = false,
    ): self {
        return new self(
            SchemaManager::create($registry, $connection),
            $connection,
            $ignoreUnknownDatabaseTypes,
        );
    }

    /**
     * @param iterable<Definition> $definitions
     *
     * @throws ToolsException
     */
    public function createSchema(iterable $definitions): void
    {
        $createSchemaSql = $this->getCreateSchemaSql($definitions);

        foreach ($createSchemaSql as $sql) {
            try {
                $this->connection->executeQuery($sql);
            } catch (Throwable $e) {
                throw ToolsException::schemaToolFailure($sql, $e);
            }
        }
    }

    /**
     * @param iterable<Definition> $definitions
     *
     * @return iterable<string>
     */
    public function getCreateSchemaSql(iterable $definitions): iterable
    {
        $schema = $this->manager->createSchema($definitions);

        return $schema->toSql();
    }

    /**
     * @param iterable<Definition> $definitions
     */
    public function dropSchema(iterable $definitions): void
    {
        $dropSchemaSql = $this->getDropSchemaSQL($definitions);

        foreach ($dropSchemaSql as $sql) {
            try {
                $this->connection->executeQuery($sql);
            } catch (Throwable) {
                // ignored
            }
        }
    }

    /**
     * @param iterable<Definition> $definitions
     *
     * @return iterable<string>
     */
    public function getDropSchemaSQL(iterable $definitions): iterable
    {
        $schema = $this->manager->createSchema($definitions);

        return $schema->toDropSql();
    }

    /**
     * @throws Exception
     */
    public function updateSchema(): void
    {
        $updateSchemaSql = $this->getUpdateSchemaSql();

        foreach ($updateSchemaSql as $sql) {
            $this->connection->executeQuery($sql);
        }
    }

    /**
     * @return iterable<string>
     */
    public function getUpdateSchemaSql(): iterable
    {
        $toSchema = $this->manager->createSchemaFromConfig();
        $fromSchema = $this->manager->createSchemaFromDatabase();

        $schemaDiff = $this->comparator->compareSchemas($fromSchema, $toSchema);

        return $schemaDiff->toSql(withoutDropping: $this->ignoreUnknownDatabaseTypes);
    }

    /**
     * @throws Exception
     */
    public function rollbackSchema(): void
    {
        $updateSchemaSql = $this->getRollbackSchemaSql();

        foreach ($updateSchemaSql as $sql) {
            $this->connection->executeQuery($sql);
        }
    }

    /**
     * @return iterable<string>
     */
    public function getRollbackSchemaSql(): iterable
    {
        $toSchema = $this->manager->createSchemaFromDatabase();
        $fromSchema = $this->manager->createSchemaFromConfig();

        $schemaDiff = $this->comparator->compareSchemas($fromSchema, $toSchema);

        return $schemaDiff->toSql(withoutCreating: $this->ignoreUnknownDatabaseTypes);
    }

    /**
     * @param iterable<Definition> $definitions
     *
     * @throws Exception
     */
    public function updateSchemaFromDefinitions(iterable $definitions): void
    {
        $updateSchemaSql = $this->getUpdateSchemaSqlFromDefinitions($definitions);

        foreach ($updateSchemaSql as $sql) {
            $this->connection->executeQuery($sql);
        }
    }

    /**
     * @param iterable<Definition> $definitions
     *
     * @return iterable<string>
     */
    public function getUpdateSchemaSqlFromDefinitions(iterable $definitions): iterable
    {
        $toSchema = $this->manager->createSchemaFromDefinitions($definitions);
        $fromSchema = $this->manager->createSchemaFromDatabase();

        $schemaDiff = $this->comparator->compareSchemas($fromSchema, $toSchema);

        return $schemaDiff->toSql(withoutDropping: $this->ignoreUnknownDatabaseTypes);
    }

    /**
     * @param iterable<Definition> $definitions
     *
     * @throws Exception
     */
    public function rollbackSchemaFromDefinitions(iterable $definitions): void
    {
        $updateSchemaSql = $this->getRollbackSchemaSqlFromDefinitions($definitions);

        foreach ($updateSchemaSql as $sql) {
            $this->connection->executeQuery($sql);
        }
    }

    /**
     * @param iterable<Definition> $definitions
     *
     * @return iterable<string>
     */
    public function getRollbackSchemaSqlFromDefinitions(iterable $definitions): iterable
    {
        $toSchema = $this->manager->createSchemaFromDatabase();
        $fromSchema = $this->manager->createSchemaFromDefinitions($definitions);

        $schemaDiff = $this->comparator->compareSchemas($fromSchema, $toSchema);

        return $schemaDiff->toSql(withoutCreating: $this->ignoreUnknownDatabaseTypes);
    }
}
