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
use Enumeum\DoctrineEnum\Schema\Schema;
use EnumeumTests\Setup\BaseTestCaseSchema;

final class SchemaTest extends BaseTestCaseSchema
{
    public function testSchemaAsDataContainerFromConstructor(): void
    {
        $name = 'some_type';
        $definition = new Definition($name, ['one', 'two', 'three']);
        $usage = new Usage($name, [
            new UsageColumn('some_type', 'some_table', 'some_column', '\'one\'::some_type'),
        ]);

        $schema = new Schema([$definition], [$usage]);

        self::assertTrue($schema->hasDefinition($name));
        self::assertEquals($definition, $schema->getDefinition($name));
        self::assertArrayHasKey($name, $schema->getDefinitions());
        self::assertContains($definition, $schema->getDefinitions());

        self::assertTrue($schema->hasUsage($name));
        self::assertEquals($usage, $schema->getUsage($name));
        self::assertArrayHasKey($name, $schema->getUsages());
        self::assertContains($usage, $schema->getUsages());
    }

    public function testSchemaAsDataContainerWithAdders(): void
    {
        $name = 'some_type';
        $definition = new Definition($name, ['one', 'two', 'three']);
        $usage = new Usage($name, [
            new UsageColumn('some_type', 'some_table', 'some_column', '\'one\'::some_type'),
        ]);

        $schema = new Schema();
        $schema->addDefinition($definition);

        self::assertTrue($schema->hasDefinition($name));
        self::assertEquals($definition, $schema->getDefinition($name));
        self::assertArrayHasKey($name, $schema->getDefinitions());
        self::assertContains($definition, $schema->getDefinitions());

        $schema->addUsage($usage);
        self::assertTrue($schema->hasUsage($name));
        self::assertEquals($usage, $schema->getUsage($name));
        self::assertArrayHasKey($name, $schema->getUsages());
        self::assertContains($usage, $schema->getUsages());
    }

    public function testSchemaAsDataContainerFromClone(): void
    {
        $name = 'some_type';
        $definition = new Definition($name, ['one', 'two', 'three']);
        $usage = new Usage($name, [
            new UsageColumn('some_type', 'some_table', 'some_column', '\'one\'::some_type'),
        ]);

        $schema = new Schema([$definition], [$usage]);
        $clone = clone $schema;

        self::assertTrue($clone->hasDefinition($name));
        self::assertEquals($schema->getDefinition($name), $clone->getDefinition($name));
        self::assertEquals($schema->getDefinitions(), $clone->getDefinitions());

        self::assertTrue($clone->hasUsage($name));
        self::assertEquals($schema->getUsage($name), $clone->getUsage($name));
        self::assertEquals($schema->getUsages(), $clone->getUsages());
    }

    public function testToSql(): void
    {
        $name = 'some_type';
        $definition = new Definition($name, ['one', 'two', 'three']);

        $schema = new Schema([$definition]);

        $updateSql = $schema->toSql();

        self::assertSame(
            [
                "CREATE TYPE some_type AS ENUM ('one', 'two', 'three')",
            ],
            $updateSql,
        );

        $this->applySQL($updateSql);
    }

    public function testToDropSql(): void
    {
        $name = 'some_type';
        $definition = new Definition($name, ['one', 'two', 'three']);

        $schema = new Schema([$definition]);

        $updateSql = $schema->toDropSql();

        self::assertSame(
            [
                "DROP TYPE IF EXISTS some_type",
            ],
            $updateSql,
        );

        $this->applySQL($updateSql);
    }

    protected function getBaseSQL(): array
    {
        return [];
    }
}
