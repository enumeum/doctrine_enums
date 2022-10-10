<?php

namespace Enumeum\DoctrineEnum;

use Enumeum\DoctrineEnum\Definition\Definition;
use Enumeum\DoctrineEnum\EnumUsage\TableUsageRegistry;

class EnumUsagePersister
{
    public function __construct(
        private readonly TableUsageRegistry $tableUsageRegistry,
        private readonly EnumQueriesGenerator $persister,
    ) {
    }

    public function getEnumTypeRemovalSql(string $tableName, string $columnName, Definition $definition): iterable
    {
        $result = [];
        if ($this->tableUsageRegistry->isUsedElsewhereExcept($definition->name, $tableName, $columnName)) {
            foreach ($this->persister->generateEnumTypePersistenceSQL($definition) as $sql) {
                if (!TypeQueriesStack::hasPersistenceQuery($sql, $definition->name)) {
                    TypeQueriesStack::addPersistenceQuery($sql, $definition->name);
                    $result[] = $sql;
                }
            }
        } else {
            foreach ($this->persister->generateEnumTypeDropSql($definition) as $sql) {
                if (!TypeQueriesStack::hasRemovalQuery($sql, $definition->name)
                    && TypeQueriesStack::isPersistenceStackEmpty($definition->name)
                    && TypeQueriesStack::isUsageStackEmpty($definition->name)
                ) {
                    TypeQueriesStack::addRemovalQuery($sql, $definition->name);
                    $result[] = $sql;
                }
            }

        }

        return $result;
    }
}
