<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum\DoctrineEnum\Exception;

use Exception;

use function sprintf;

class MappingException extends Exception
{
    public static function fileMappingDriversRequireConfiguredDirectoryPath(?string $path = null): self
    {
        if (null !== $path) {
            $path = '[' . $path . ']';
        }

        return new self(sprintf(
            'File mapping drivers must have a valid directory path, ' .
            'however the given path %s seems to be incorrect!',
            $path,
        ));
    }

    public static function typeWithSameNameAlreadyLoadedFromAnotherEnum(
        string $name,
        string $currentType,
        string $loadedType,
    ): self {
        return new self(sprintf(
            'Type with name "%s" was already loaded from enum "%s", but attempted to be loaded again from enum "%s".',
            $name,
            $loadedType,
            $currentType,
        ));
    }

    public static function typeMappedOnNonBackedEnumWhichNotSupported(
        string $type,
    ): self {
        return new self(sprintf('Type with name "%s" is not a BackedEnum, thus does not supported.', $type));
    }
}
