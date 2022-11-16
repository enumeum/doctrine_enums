<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine enumerations extension for Postgres" package.
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
            $path = '['.$path.']';
        }

        return new self(sprintf(
            'File mapping drivers must have a valid directory path, '.
            'however the given path %s seems to be incorrect!',
            $path
        ));
    }
}
