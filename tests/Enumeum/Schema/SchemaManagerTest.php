<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EnumeumTests\Schema;

use Enumeum\DoctrineEnum\Definition\Definition;
use Enumeum\DoctrineEnum\EnumUsage\Usage;
use Enumeum\DoctrineEnum\EnumUsage\UsageColumn;
use Enumeum\DoctrineEnum\Schema\SchemaManager;
use EnumeumTests\Fixture\SomeStatusType;
use EnumeumTests\Setup\BaseTestCaseSchema;

use function array_shift;
use function assert;

final class SchemaManagerTest extends BaseTestCaseSchema
{
    public function testCreateSchema(): void
    {
        $name = 'some_type';
        $definition = new Definition($name, ['one', 'two', 'three']);
        $usage = new Usage($name, [
            new UsageColumn('some_type', 'some_table', 'some_column', '\'one\'::some_type'),
        ]);

        $manager = $this->createSchemaManager();
        $schema = $manager->createSchema([$definition], [$usage]);

        self::assertTrue($schema->hasDefinition($name));
        self::assertSame($definition, $schema->getDefinition($name));
        self::assertArrayHasKey($name, $schema->getDefinitions());
        self::assertContains($definition, $schema->getDefinitions());

        self::assertTrue($schema->hasUsage($name));
        self::assertSame($usage, $schema->getUsage($name));
        self::assertArrayHasKey($name, $schema->getUsages());
        self::assertContains($usage, $schema->getUsages());
    }

    public function testCreateSchemaFromDefinitions(): void
    {
        $this->applySQL([
            "CREATE TYPE some_type AS ENUM ('one', 'two')",
            'CREATE TABLE some_table (id INT NOT NULL, PRIMARY KEY(id), some_column some_type NOT NULL)',
            "INSERT INTO some_table VALUES (1, 'two')",
        ]);

        $name = 'some_type';
        $table = 'some_table';
        $column = 'some_column';
        $definition = new Definition($name, ['one', 'two', 'three']);

        $manager = $this->createSchemaManager();
        $schema = $manager->createSchemaFromDefinitions([$definition]);

        self::assertTrue($schema->hasDefinition($name));
        self::assertNotNull($schema->getDefinition($name));
        self::assertSame(['one', 'two', 'three'], $schema->getDefinition($name)->cases);
        self::assertArrayHasKey($name, $schema->getDefinitions());

        self::assertTrue($schema->hasUsage($name));
        self::assertNotNull($schema->getUsage($name));

        $columns = $schema->getUsage($name)->columns;
        self::assertCount(1, $columns);
        $usageColumn = array_shift($columns);
        assert($usageColumn instanceof UsageColumn);
        self::assertSame($name, $usageColumn->name);
        self::assertSame($table, $usageColumn->table);
        self::assertSame($column, $usageColumn->column);

        self::assertArrayHasKey($name, $schema->getUsages());
    }

    public function testCreateSchemaFromConfig(): void
    {
        $this->applySQL([
            "CREATE TYPE some_type AS ENUM ('one', 'two')",
            'CREATE TABLE some_table (id INT NOT NULL, PRIMARY KEY(id), some_column some_type NOT NULL)',
            "INSERT INTO some_table VALUES (1, 'two')",
        ]);

        $name = 'some_type';
        $table = 'some_table';
        $column = 'some_column';

        $this->registerTypes([SomeStatusType::class]);

        $manager = $this->createSchemaManager();
        $schema = $manager->createSchemaFromConfig();

        self::assertTrue($schema->hasDefinition($name));
        self::assertNotNull($schema->getDefinition($name));
        self::assertSame(['one', 'two', 'three'], $schema->getDefinition($name)->cases);
        self::assertArrayHasKey($name, $schema->getDefinitions());

        self::assertTrue($schema->hasUsage($name));
        self::assertNotNull($schema->getUsage($name));

        $columns = $schema->getUsage($name)->columns;
        self::assertCount(1, $columns);
        $usageColumn = array_shift($columns);
        assert($usageColumn instanceof UsageColumn);
        self::assertSame($name, $usageColumn->name);
        self::assertSame($table, $usageColumn->table);
        self::assertSame($column, $usageColumn->column);

        self::assertArrayHasKey($name, $schema->getUsages());
    }

    public function testCreateSchemaFromDatabase(): void
    {
        $this->applySQL([
            "CREATE TYPE some_type AS ENUM ('one', 'two')",
            'CREATE TABLE some_table (id INT NOT NULL, PRIMARY KEY(id), some_column some_type NOT NULL)',
            "INSERT INTO some_table VALUES (1, 'two')",
        ]);

        $name = 'some_type';
        $table = 'some_table';
        $column = 'some_column';

        $manager = $this->createSchemaManager();
        $schema = $manager->createSchemaFromDatabase();

        self::assertTrue($schema->hasDefinition($name));
        self::assertNotNull($schema->getDefinition($name));
        self::assertSame(['one', 'two'], $schema->getDefinition($name)->cases);
        self::assertArrayHasKey($name, $schema->getDefinitions());

        self::assertTrue($schema->hasUsage($name));
        self::assertNotNull($schema->getUsage($name));

        $columns = $schema->getUsage($name)->columns;
        self::assertCount(1, $columns);
        $usageColumn = array_shift($columns);
        assert($usageColumn instanceof UsageColumn);
        self::assertSame($name, $usageColumn->name);
        self::assertSame($table, $usageColumn->table);
        self::assertSame($column, $usageColumn->column);

        self::assertArrayHasKey($name, $schema->getUsages());
    }

    protected function getBaseSQL(): array
    {
        return [];
    }

    private function createSchemaManager(): SchemaManager
    {
        return new SchemaManager(
            $this->getDefinitionRegistry(),
            $this->getDatabaseDefinitionRegistry($this->em->getConnection()),
            $this->getTableUsageRegistry($this->em->getConnection()),
        );
    }
}
