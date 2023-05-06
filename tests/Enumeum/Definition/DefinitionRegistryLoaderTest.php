<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EnumeumTests\Definition;

use Enumeum\DoctrineEnum\Definition\DefinitionRegistry;
use Enumeum\DoctrineEnum\Definition\DefinitionRegistryLoader;
use Enumeum\DoctrineEnum\Definition\EnumClassLocator;
use Enumeum\DoctrineEnum\Exception\MappingException;
use EnumeumTests\Fixture\AnotherStatusType;
use EnumeumTests\Fixture\BaseStatusType;
use EnumeumTests\Fixture\DefinitionEnum\One\AlphaStatusType;
use EnumeumTests\Fixture\DefinitionEnum\One\BetaStatusType;
use EnumeumTests\Fixture\DefinitionEnum\One\GammaStatusType;
use EnumeumTests\Fixture\DuplicatedBaseStatusType;
use EnumeumTests\Fixture\GeneralEnumStatusType;
use EnumeumTests\Fixture\IntegerStatusType;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class DefinitionRegistryLoaderTest extends TestCase
{
    public function testStaticNullableCreate(): void
    {
        $loader = DefinitionRegistryLoader::create();
        self::assertInstanceOf(DefinitionRegistryLoader::class, $loader);
        self::assertInstanceOf(DefinitionRegistry::class, $loader->getRegistry());
    }

    public function testStaticCreate(): void
    {
        $loader = DefinitionRegistryLoader::create(
            new EnumClassLocator([]),
            [
                AlphaStatusType::class,
                BetaStatusType::class,
                GammaStatusType::class,
            ],
            [
                [
                    DefinitionRegistryLoader::DIR_KEY => __DIR__ . '/../Fixture/DefinitionEnum/Two',
                    DefinitionRegistryLoader::NAMESPACE_KEY => 'EnumeumTests\Fixture\DefinitionEnum\Two',
                ],
            ],
        );
        self::assertInstanceOf(DefinitionRegistryLoader::class, $loader);
    }

    public function testLoadType(): void
    {
        $loader = DefinitionRegistryLoader::create();
        $loader->loadType(BaseStatusType::class);

        $types = $loader->getRegistry()->getDefinitions();

        self::assertCount(1, $types);
        self::assertArrayHasKey('status_type', $types);
    }

    public function testLoadIntegerBackedType(): void
    {
        $loader = DefinitionRegistryLoader::create();
        $loader->loadType(IntegerStatusType::class);

        $types = $loader->getRegistry()->getDefinitions();

        self::assertCount(1, $types);
        self::assertArrayHasKey('status_type', $types);
    }

    public function testLoadGeneralEnumType(): void
    {
        $loader = DefinitionRegistryLoader::create();
        $loader->loadType(GeneralEnumStatusType::class);

        self::expectException(MappingException::class);
        self::expectExceptionMessage(
            'Type with name "EnumeumTests\Fixture\GeneralEnumStatusType" is not a BackedEnum, thus does not supported.',
        );

        $loader->getRegistry()->getDefinitions();
    }

    public function testLoadAlreadyLoadedEnum(): void
    {
        $loader = DefinitionRegistryLoader::create();
        $loader->loadType(BaseStatusType::class);

        /* Adding same enum. */
        $loader->loadType(BaseStatusType::class);

        $types = $loader->getRegistry()->getDefinitions();

        self::assertCount(1, $types);
        self::assertArrayHasKey('status_type', $types);
    }

    public function testLoadAnotherEnumWithAlreadyLoadedName(): void
    {
        $loader = DefinitionRegistryLoader::create();
        $loader->loadType(BaseStatusType::class);

        /* Adding another enum, but with same name. */
        $loader->loadType(DuplicatedBaseStatusType::class);

        self::expectException(MappingException::class);
        self::expectExceptionMessage(
            'Type with name "status_type" was already loaded from enum "EnumeumTests\Fixture\BaseStatusType", ' .
            'but attempted to be loaded again from enum "EnumeumTests\Fixture\DuplicatedBaseStatusType".',
        );

        $loader->getRegistry()->getDefinitions();
    }

    public function testLoadTypes(): void
    {
        $loader = DefinitionRegistryLoader::create();
        $loader->loadTypes([
            BaseStatusType::class,
            AnotherStatusType::class,
        ]);

        $types = $loader->getRegistry()->getDefinitions();

        self::assertCount(2, $types);
        self::assertArrayHasKey('status_type', $types);
        self::assertArrayHasKey('another_status_type', $types);
    }

    public function testLoadDir(): void
    {
        $loader = DefinitionRegistryLoader::create();
        $loader->loadDir(
            __DIR__ . '/../Fixture/DefinitionEnum/One',
            'EnumeumTests\Fixture\DefinitionEnum\One',
        );

        $types = $loader->getRegistry()->getDefinitions();

        self::assertCount(3, $types);
        self::assertArrayHasKey('alpha_status_type_one', $types);
        self::assertArrayHasKey('beta_status_type_one', $types);
        self::assertArrayHasKey('gamma_status_type_one', $types);
    }

    public function testLoadDirs(): void
    {
        $loader = DefinitionRegistryLoader::create();
        $loader->loadDirs([
            [
                DefinitionRegistryLoader::DIR_KEY => __DIR__ . '/../Fixture/DefinitionEnum/One',
                DefinitionRegistryLoader::NAMESPACE_KEY => 'EnumeumTests\Fixture\DefinitionEnum\One',
            ],
            [
                DefinitionRegistryLoader::DIR_KEY => __DIR__ . '/../Fixture/DefinitionEnum/Two',
                DefinitionRegistryLoader::NAMESPACE_KEY => 'EnumeumTests\Fixture\DefinitionEnum\Two',
            ],
        ]);

        $types = $loader->getRegistry()->getDefinitions();

        self::assertCount(6, $types);
        self::assertArrayHasKey('alpha_status_type_one', $types);
        self::assertArrayHasKey('beta_status_type_one', $types);
        self::assertArrayHasKey('gamma_status_type_one', $types);
        self::assertArrayHasKey('alpha_status_type_two', $types);
        self::assertArrayHasKey('beta_status_type_two', $types);
        self::assertArrayHasKey('gamma_status_type_two', $types);
    }
}
