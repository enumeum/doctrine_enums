<?php

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
