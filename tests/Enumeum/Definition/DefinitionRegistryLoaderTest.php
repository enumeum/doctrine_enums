<?php declare(strict_types=1);

namespace EnumeumTests\Definition;

use Enumeum\DoctrineEnum\Definition\DefinitionRegistry;
use Enumeum\DoctrineEnum\Definition\DefinitionRegistryLoader;
use Enumeum\DoctrineEnum\Definition\EnumClassLocator;
use EnumeumTests\Fixture\DefinitionEnum\One\AlphaStatusType;
use EnumeumTests\Fixture\DefinitionEnum\One\BetaStatusType;
use EnumeumTests\Fixture\DefinitionEnum\One\GammaStatusType;
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
            new DefinitionRegistry(),
            new EnumClassLocator([]),
            [
                AlphaStatusType::class,
                BetaStatusType::class,
                GammaStatusType::class,
            ],
            [
                [
                    DefinitionRegistryLoader::PATH_KEY => __DIR__ . '/../Fixture/DefinitionEnum/Two',
                    DefinitionRegistryLoader::NAMESPACE_KEY => 'EnumeumTests\Fixture\DefinitionEnum\Two',
                ]
            ],
        );
        self::assertInstanceOf(DefinitionRegistryLoader::class, $loader);
    }

    public function testLoadType(): void
    {
        $loader = DefinitionRegistryLoader::create();
        $loader->loadType(AlphaStatusType::class);

        $types = $loader->getRegistry()->getDefinitionsHashedByName();

        self::assertCount(1, $types);
        self::assertArrayHasKey('alpha_status_type_one', $types);
    }

    public function testLoadTypes(): void
    {
        $loader = DefinitionRegistryLoader::create();
        $loader->loadTypes([
            AlphaStatusType::class,
            BetaStatusType::class,
        ]);

        $types = $loader->getRegistry()->getDefinitionsHashedByName();

        self::assertCount(2, $types);
        self::assertArrayHasKey('alpha_status_type_one', $types);
        self::assertArrayHasKey('beta_status_type_one', $types);
    }

    public function testLoadDir(): void
    {
        $loader = DefinitionRegistryLoader::create();
        $loader->loadDir(
            __DIR__ . '/../Fixture/DefinitionEnum/One',
            'EnumeumTests\Fixture\DefinitionEnum\One',
        );

        $types = $loader->getRegistry()->getDefinitionsHashedByName();

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
                DefinitionRegistryLoader::PATH_KEY => __DIR__ . '/../Fixture/DefinitionEnum/One',
                DefinitionRegistryLoader::NAMESPACE_KEY => 'EnumeumTests\Fixture\DefinitionEnum\One',
            ],
            [
                DefinitionRegistryLoader::PATH_KEY => __DIR__ . '/../Fixture/DefinitionEnum/Two',
                DefinitionRegistryLoader::NAMESPACE_KEY => 'EnumeumTests\Fixture\DefinitionEnum\Two',
            ],
        ]);

        $types = $loader->getRegistry()->getDefinitionsHashedByName();

        self::assertCount(6, $types);
        self::assertArrayHasKey('alpha_status_type_one', $types);
        self::assertArrayHasKey('beta_status_type_one', $types);
        self::assertArrayHasKey('gamma_status_type_one', $types);
        self::assertArrayHasKey('alpha_status_type_two', $types);
        self::assertArrayHasKey('beta_status_type_two', $types);
        self::assertArrayHasKey('gamma_status_type_two', $types);
    }
}
