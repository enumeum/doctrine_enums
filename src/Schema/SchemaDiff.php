<?php

declare(strict_types=1);

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
        /** @var iterable<DefinitionDiff> $reorderChangeSet */
        public readonly iterable $reorderChangeSet = [],
        /** @var iterable<Definition> $dropChangeSet */
        public readonly iterable $dropChangeSet = [],
    ) {
    }

    /**
     * @return iterable<string>
     */
    public function toSql(): iterable
    {
        $builder = QueryBuilder::create();

        return array_merge(
            $builder->generateEnumDropQueries($this->dropChangeSet),
            $builder->generateEnumReorderQueries($this->reorderChangeSet),
            $builder->generateEnumAlterQueries($this->alterChangeSet),
            $builder->generateEnumCreateQueries($this->createChangeSet),
        );
    }
}
