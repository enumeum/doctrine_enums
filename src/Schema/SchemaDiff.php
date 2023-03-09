<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum\DoctrineEnum\Schema;

use Enumeum\DoctrineEnum\Definition\Definition;
use Enumeum\DoctrineEnum\QueryBuild\QueryBuilder;

use function array_merge;

class SchemaDiff
{
    public function __construct(
        /** @var iterable<Definition> $createChangeSet */
        public readonly iterable $createChangeSet = [],
        /** @var iterable<DefinitionDiff> $alterChangeSet */
        public readonly iterable $alterChangeSet = [],
        /** @var iterable<Definition> $dropChangeSet */
        public readonly iterable $dropChangeSet = [],
    ) {
    }

    /**
     * @return iterable<string>
     */
    public function toSql(bool $withoutDropping = false, bool $withoutCreating = false): iterable
    {
        $builder = QueryBuilder::create();

        return array_merge(
            $withoutDropping ? [] : $builder->generateEnumDropQueries($this->dropChangeSet),
            $builder->generateEnumAlterQueries($this->alterChangeSet),
            $withoutCreating ? [] : $builder->generateEnumCreateQueries($this->createChangeSet),
        );
    }
}
