<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EnumeumTests\Definition;

use Enumeum\DoctrineEnum\Definition\EnumClassLocator;
use EnumeumTests\Fixture\DefinitionEnum\One\AlphaStatusType;
use EnumeumTests\Fixture\DefinitionEnum\One\BetaStatusType;
use EnumeumTests\Fixture\DefinitionEnum\One\GammaStatusType;
use EnumeumTests\Fixture\DefinitionEnum\Two\AlphaStatusType as AlphaTwo;
use EnumeumTests\Fixture\DefinitionEnum\Two\BetaStatusType as BetaTwo;
use EnumeumTests\Fixture\DefinitionEnum\Two\GammaStatusType as GammaTwo;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class EnumClassLocatorTest extends TestCase
{
    public function testCreate(): void
    {
        $locator = new EnumClassLocator([], '.php');
        $found = $locator->findEnumClassNames('');

        self::assertCount(0, $found);
    }

    public function testAddPathsWithContruct(): void
    {
        $locator = new EnumClassLocator([__DIR__.'/../Fixture/DefinitionEnum/One']);
        $found = $locator->findEnumClassNames('EnumeumTests\Fixture\DefinitionEnum\One');

        self::assertCount(3, $found);
        self::assertContains(AlphaStatusType::class, $found);
        self::assertContains(BetaStatusType::class, $found);
        self::assertContains(GammaStatusType::class, $found);
    }

    public function testAddPaths(): void
    {
        $locator = new EnumClassLocator([]);
        $locator->addPaths([__DIR__.'/../Fixture/DefinitionEnum/One']);
        $found = $locator->findEnumClassNames('EnumeumTests\Fixture\DefinitionEnum\One');

        self::assertCount(3, $found);
        self::assertContains(AlphaStatusType::class, $found);
        self::assertContains(BetaStatusType::class, $found);
        self::assertContains(GammaStatusType::class, $found);
    }

    public function testAddPathsSimultaneously(): void
    {
        $locator = new EnumClassLocator([__DIR__.'/../Fixture/DefinitionEnum/One']);
        $locator->addPaths([__DIR__.'/../Fixture/DefinitionEnum/Two']);

        $found = $locator->findEnumClassNames('EnumeumTests\Fixture\DefinitionEnum\One');

        self::assertCount(3, $found);
        self::assertContains(AlphaStatusType::class, $found);
        self::assertContains(BetaStatusType::class, $found);
        self::assertContains(GammaStatusType::class, $found);

        $found = $locator->findEnumClassNames('EnumeumTests\Fixture\DefinitionEnum\Two');
        self::assertCount(3, $found);
        self::assertContains(AlphaTwo::class, $found);
        self::assertContains(BetaTwo::class, $found);
        self::assertContains(GammaTwo::class, $found);
    }

    public function testAddSamePathsSimultaneously(): void
    {
        $locator = new EnumClassLocator([__DIR__.'/../Fixture/DefinitionEnum/One']);
        $locator->addPaths([__DIR__.'/../Fixture/DefinitionEnum/One']);
        $locator->addPaths([__DIR__.'/../Fixture/DefinitionEnum/One']);

        $found = $locator->findEnumClassNames('EnumeumTests\Fixture\DefinitionEnum\One');

        self::assertCount(3, $found);
        self::assertContains(AlphaStatusType::class, $found);
        self::assertContains(BetaStatusType::class, $found);
        self::assertContains(GammaStatusType::class, $found);
    }

    public function testFindEnumsRecursively(): void
    {
        $locator = new EnumClassLocator([__DIR__.'/../Fixture/DefinitionEnum']);

        $found = $locator->findEnumClassNames('EnumeumTests\Fixture\DefinitionEnum');

        self::assertCount(6, $found);
        self::assertContains(AlphaStatusType::class, $found);
        self::assertContains(BetaStatusType::class, $found);
        self::assertContains(GammaStatusType::class, $found);
        self::assertContains(AlphaTwo::class, $found);
        self::assertContains(BetaTwo::class, $found);
        self::assertContains(GammaTwo::class, $found);
    }

    public function testFindEnumsMoreThanOnce(): void
    {
        $locator = new EnumClassLocator([__DIR__.'/../Fixture/DefinitionEnum']);

        $found = $locator->findEnumClassNames('EnumeumTests\Fixture\DefinitionEnum');

        self::assertCount(6, $found);
        self::assertContains(AlphaStatusType::class, $found);
        self::assertContains(BetaStatusType::class, $found);
        self::assertContains(GammaStatusType::class, $found);
        self::assertContains(AlphaTwo::class, $found);
        self::assertContains(BetaTwo::class, $found);
        self::assertContains(GammaTwo::class, $found);

        $found = $locator->findEnumClassNames('EnumeumTests\Fixture\DefinitionEnum');

        self::assertCount(6, $found);
        self::assertContains(AlphaStatusType::class, $found);
        self::assertContains(BetaStatusType::class, $found);
        self::assertContains(GammaStatusType::class, $found);
        self::assertContains(AlphaTwo::class, $found);
        self::assertContains(BetaTwo::class, $found);
        self::assertContains(GammaTwo::class, $found);
    }

    public function testFindEnumsSameNameFromAnotherNamespace(): void
    {
        $locator = new EnumClassLocator([__DIR__.'/../Fixture/DefinitionEnum/Two']);

        $found = $locator->findEnumClassNames('EnumeumTests\Fixture\DefinitionEnum\One');

        self::assertCount(0, $found);
    }
}
