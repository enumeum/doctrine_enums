<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum\DoctrineEnum\Definition;

use Enumeum\DoctrineEnum\Exception\MappingException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use function array_merge;
use function array_unique;
use function assert;
use function in_array;
use function is_dir;
use function sprintf;
use function str_replace;
use function trim;

class EnumClassLocator
{
    /** @var iterable<int, string> */
    protected iterable $paths = [];

    protected string $fileExtension = '.php';

    public function __construct(iterable $paths, ?string $fileExtension = null)
    {
        $this->addPaths($paths);
        $this->fileExtension = $fileExtension ?? $this->fileExtension;
    }

    public function addPaths(iterable $paths): void
    {
        $this->paths = array_unique(array_merge($this->paths, [...$paths]));
    }

    public function findEnumClassNames(string $globalBasename): iterable
    {
        if ([] === $this->paths) {
            return [];
        }

        $enums = [];
        foreach ($this->paths as $path) {
            if (!is_dir($path)) {
                throw MappingException::fileMappingDriversRequireConfiguredDirectoryPath($path);
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path),
                RecursiveIteratorIterator::LEAVES_ONLY,
            );

            foreach ($iterator as $file) {
                assert($file instanceof SplFileInfo);

                if (in_array($fileName = $file->getBasename(), ['.', '..'], true)) {
                    continue;
                }

                $subSpace = str_replace($fileName, '', $file->getPathname());
                $subSpace = str_replace($path, '', $subSpace);
                $subSpace = trim($subSpace, '/');

                $className = $file->getBasename($this->fileExtension);
                if ($className === $file->getBasename() || $className === $globalBasename) {
                    continue;
                }

                require_once $file->getPathname();

                $class = sprintf('%s\\%s%s', $globalBasename, $subSpace . ($subSpace ? '\\' : ''), $className);
                if (enum_exists($class, false)) {
                    $enums[] = $class;
                }
            }
        }

        return array_unique($enums);
    }
}
