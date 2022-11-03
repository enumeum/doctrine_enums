<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine enumerations extension for Postgres" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum\DoctrineEnum;

use function array_merge;

class EnumTool
{
    public function __construct(
        private readonly DatabaseUpdateQueryBuilder $queryBuilder,
    ) {
    }

    /**
     * @return iterable<string>
     */
    public function __invoke(): iterable
    {
        return array_merge(
            $this->queryBuilder->generateEnumDropQueries(),
            $this->queryBuilder->generateEnumReorderQueries(),
            $this->queryBuilder->generateEnumAlterQueries(),
            $this->queryBuilder->generateEnumCreateQueries(),
        );
    }
}
