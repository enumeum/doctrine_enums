<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EnumeumTests\Setup;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Logging\Middleware;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Enumeum\DoctrineEnum\DatabaseUpdateQueryBuilder;
use Enumeum\DoctrineEnum\Definition\DatabaseDefinitionRegistry;
use Enumeum\DoctrineEnum\Definition\DefinitionRegistry;
use Enumeum\DoctrineEnum\EnumUsage\MaterialViewUsageRegistry;
use Enumeum\DoctrineEnum\EnumUsage\TableColumnRegistry;
use Enumeum\DoctrineEnum\EnumUsage\TableUsageRegistry;
use Enumeum\DoctrineEnum\Type\TypeRegistryLoader;
use Enumeum\DoctrineEnum\TypeQueriesStack;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

abstract class BaseTestCase extends TestCase
{
    protected readonly array $params;
    protected ?EntityManager $em = null;
    protected ?DefinitionRegistry $definitionRegistry = null;
    protected ?DatabaseDefinitionRegistry $databaseDefinitionRegistry = null;
    protected ?TableUsageRegistry $tableUsageRegistry = null;
    protected ?TableColumnRegistry $tableColumnRegistry = null;
    protected ?MaterialViewUsageRegistry $materialViewUsageRegistry = null;
    protected ?DatabaseUpdateQueryBuilder $databaseUpdateQueryBuilder = null;
    protected QueryAnalyzer $queryAnalyzer;
    protected MockObject|LoggerInterface $queryLogger;

    protected function setUp(): void
    {
        $params = $this->getConnectionParams();

        $this->resetDatabase($params);

        $this->queryLogger = $this->createMock(LoggerInterface::class);
        $em = $this->getDefaultMockEntityManager($params);
        $this->setupPrerequisites($em);

        TypeQueriesStack::reset();
    }

    protected function tearDown(): void
    {
        if (null === $this->em) {
            return;
        }

        $this->em->getConnection()->close();
        $this->dropDatabase($this->getConnectionParams());

        $this->em = null;
    }

    /**
     * @return string[]
     */
    abstract protected function getBaseSQL(): array;

    abstract protected function getConnectionParams(): array;

    abstract protected function getDefaultMockEntityManager(
        array $params,
        EventManager $evm = null,
        Configuration $config = null
    ): EntityManager;

    /**
     * TODO: Remove this method when dropping support of doctrine/dbal 2.
     *
     * @throws RuntimeException|Exception
     */
    protected function startQueryLog(): void
    {
        if (null === $this->em || null === $this->em->getConnection()->getDatabasePlatform()) {
            throw new RuntimeException('EntityManager and database platform must be initialized');
        }
        $this->queryAnalyzer = new QueryAnalyzer($this->em->getConnection()->getDatabasePlatform());
        $this->em->getConfiguration()->setSQLLogger($this->queryAnalyzer);
    }

    /**
     * @throws Exception
     */
    protected function resetDatabase(array $params): void
    {
        $this->dropDatabase($params);
        $this->createDatabase($params);
    }

    /**
     * @throws Exception
     */
    protected function createDatabase(array $params): void
    {
        $name = $params['dbname'];
        unset($params['dbname']);
        $connection = DriverManager::getConnection($params);
        $schemaManager = $connection->createSchemaManager();

        if (!in_array($name, $schemaManager->listDatabases(), true)) {
            $schemaManager->createDatabase($name);
        }

        $connection->close();
    }

    /**
     * @throws Exception
     */
    protected function dropDatabase(array $params): void
    {
        $name = $params['dbname'];
        unset($params['dbname']);
        $connection = DriverManager::getConnection($params);
        $schemaManager = $connection->createSchemaManager();

        if (in_array($name, $schemaManager->listDatabases(), true)) {
            $schemaManager->dropDatabase($name);
        }

        $connection->close();
    }

    /**
     * @param iterable<class-string> $types
     */
    protected function registerTypes(iterable $types): void
    {
        $this->getDefinitionRegistry()->load($types);
        TypeRegistryLoader::load($this->getDefinitionRegistry()->getDefinitions());
    }

    protected function setupPrerequisites(EntityManager $em): void
    {
        array_map(
            static function ($sql) use ($em) {
                return $em->getConnection()->executeQuery($sql);
            },
            $this->getBaseSQL(),
        );
    }

    protected function getMetadataDriverImplementation(): MappingDriver
    {
        return new AttributeDriver([]);
        // return new AnnotationDriver(new AnnotationReader());
    }

    protected function getDefaultConfiguration(): Configuration
    {
        $config = new Configuration();
        $config->setProxyDir(TESTS_TEMP_DIR);
        $config->setProxyNamespace('Proxy');
        $config->setMetadataDriverImpl($this->getMetadataDriverImplementation());

        $config->setMiddlewares([
            new Middleware($this->queryLogger),
        ]);

        return $config;
    }

    /**
     * @return ClassMetadata[]
     */
    protected function composeSchema(array $classes): array
    {
        $em = $this->em;

        return array_map(
            static function ($class) use ($em) {
                return $em->getClassMetadata($class);
            },
            $classes,
        );
    }

    /**
     * @throws Exception
     */
    protected function applySQL(iterable $queries): void
    {
        foreach ($queries as $sql) {
            $this->em->getConnection()->executeQuery($sql);
        }
    }

    /**
     * @throws Exception
     */
    protected function applySQLWithinTransaction(iterable $queries): void
    {
        $this->em->getConnection()->beginTransaction();
        $this->applySQL($queries);
        $this->em->getConnection()->commit();
    }

    protected function getDatabaseUpdateQueryBuilder(): DatabaseUpdateQueryBuilder
    {
        if (null === $this->databaseUpdateQueryBuilder) {
            $this->databaseUpdateQueryBuilder = new DatabaseUpdateQueryBuilder(
                $this->getDefinitionRegistry(),
                $this->getDatabaseDefinitionRegistry($this->em->getConnection()),
                $this->getTableUsageRegistry($this->em->getConnection()),
            );
        }

        return $this->databaseUpdateQueryBuilder;
    }

    protected function getDefinitionRegistry(): DefinitionRegistry
    {
        if (null === $this->definitionRegistry) {
            $this->definitionRegistry = new DefinitionRegistry();
        }

        return $this->definitionRegistry;
    }

    protected function getDatabaseDefinitionRegistry(Connection $connection): DatabaseDefinitionRegistry
    {
        if (null === $this->databaseDefinitionRegistry) {
            $this->databaseDefinitionRegistry = new DatabaseDefinitionRegistry($connection);
        }

        return $this->databaseDefinitionRegistry;
    }

    protected function getTableUsageRegistry(Connection $connection): TableUsageRegistry
    {
        if (null === $this->tableUsageRegistry) {
            $this->tableUsageRegistry = new TableUsageRegistry($connection);
        }

        return $this->tableUsageRegistry;
    }

    protected function getTableColumnRegistry(Connection $connection): TableColumnRegistry
    {
        if (null === $this->tableUsageRegistry) {
            $this->tableColumnRegistry = new TableColumnRegistry($connection);
        }

        return $this->tableColumnRegistry;
    }

    protected function getMaterialViewUsageRegistry(Connection $connection): MaterialViewUsageRegistry
    {
        if (null === $this->materialViewUsageRegistry) {
            $this->materialViewUsageRegistry = new MaterialViewUsageRegistry($connection);
        }

        return $this->materialViewUsageRegistry;
    }
}
