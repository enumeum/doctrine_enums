<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum\DoctrineEnum\EnumUsage;

class UsageColumn
{
    public function __construct(
        public readonly string $name,
        public readonly string $table,
        public readonly string $column,
        public readonly ?string $default,
    ) {
    }
}
