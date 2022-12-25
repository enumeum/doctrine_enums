<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum\DoctrineEnum\Definition;

class DefinitionRegistryLoader
{
    public const DIR_KEY = 'dir';
    public const NAMESPACE_KEY = 'namespace';

    /**
     * @param iterable<class-string>                           $enumClassNames
     * @param iterable<array{path: string, namespace: string}> $enumDirPaths
     */
    public function __construct(
        private readonly DefinitionRegistry $registry,
        private readonly EnumClassLocator $locator,
        iterable $enumClassNames = [],
        iterable $enumDirPaths = [],
    ) {
        $this->loadTypes($enumClassNames);
        $this->loadDirs($enumDirPaths);
    }

    /**
     * @param iterable<class-string>|null                          $enumClassNames
     * @param iterable<array{dir: string, namespace: string}>|null $enumDirPaths
     */
    public static function create(
        ?DefinitionRegistry $registry = null,
        ?EnumClassLocator $locator = null,
        iterable $enumClassNames = [],
        iterable $enumDirPaths = [],
    ): self {
        return new self(
            $registry ?? new DefinitionRegistry(),
            $locator ?? new EnumClassLocator([]),
            $enumClassNames,
            $enumDirPaths
        );
    }

    public function getRegistry(): DefinitionRegistry
    {
        return $this->registry;
    }

    /**
     * @param class-string $name
     */
    public function loadType(string $name): void
    {
        $this->registry->loadType($name);
    }

    /**
     * @param iterable<class-string> $names
     */
    public function loadTypes(iterable $names): void
    {
        foreach ($names as $name) {
            $this->registry->loadType($name);
        }
    }

    public function loadDir(string $path, string $namespace): void
    {
        $this->locator->addPaths([$path]);

        $enums = $this->locator->findEnumClassNames($namespace);
        foreach ($enums as $enum) {
            $this->loadType($enum);
        }
    }

    /**
     * @param iterable<array{path: string, namespace: string}> $dirPaths
     */
    public function loadDirs(iterable $dirPaths): void
    {
        foreach ($dirPaths as [self::DIR_KEY => $path, self::NAMESPACE_KEY => $namespace]) {
            $this->loadDir($path, $namespace);
        }
    }
}
