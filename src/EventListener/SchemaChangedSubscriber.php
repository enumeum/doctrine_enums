<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum\DoctrineEnum\EventListener;

use Doctrine\Common\EventManager;
use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Event\SchemaAlterTableAddColumnEventArgs;
use Doctrine\DBAL\Event\SchemaAlterTableChangeColumnEventArgs;
use Doctrine\DBAL\Event\SchemaAlterTableRemoveColumnEventArgs;
use Doctrine\DBAL\Event\SchemaCreateTableColumnEventArgs;
use Doctrine\DBAL\Events;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\TableDiff;
use Enumeum\DoctrineEnum\Definition\DatabaseDefinitionRegistry;
use Enumeum\DoctrineEnum\Definition\Definition;
use Enumeum\DoctrineEnum\Definition\DefinitionRegistry;
use Enumeum\DoctrineEnum\EnumUsage\TableUsageRegistry;
use Enumeum\DoctrineEnum\QueryBuild\EnumQueryBuilder;
use Enumeum\DoctrineEnum\Tools\CommentMarker;
use Enumeum\DoctrineEnum\Tools\EnumChangesTool;
use Enumeum\DoctrineEnum\Type\GenericEnumType;
use Enumeum\DoctrineEnum\TypeQueriesStack;

use function array_push;

class SchemaChangedSubscriber implements EventSubscriber
{
    public const ENUM_TYPE_OPTION_NAME = 'enumType';

    public function __construct(
        private readonly DefinitionRegistry $registry,
        private readonly DatabaseDefinitionRegistry $databaseRegistry,
        private readonly TableUsageRegistry $tableUsageRegistry,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        return [
            Events::onSchemaCreateTableColumn,
            Events::onSchemaAlterTableAddColumn,
            Events::onSchemaAlterTableChangeColumn,
            Events::onSchemaAlterTableRemoveColumn,
        ];
    }

    public function onSchemaCreateTableColumn(SchemaCreateTableColumnEventArgs $event): void
    {
        if (!$event->getPlatform() instanceof PostgreSqlPlatform) {
            return;
        }

        $column = $event->getColumn();
        if (null === $definition = $this->findTypeDefinition($column)) {
            return;
        }

        foreach ($this->getEnumTypePersistenceSQL($definition) as $sql) {
            if (!TypeQueriesStack::hasPersistenceQuery($sql, $definition->name)) {
                TypeQueriesStack::addPersistenceQuery($sql, $definition->name);
                $event->addSql($sql);
            }
        }

        $platform = $event->getPlatform();
        $column->setType(GenericEnumType::create($definition->name));
        $tableDiff = new TableDiff($event->getTable()->getName(), [$column]);
        foreach ($this->getAlterTableColumnSQL($platform, $tableDiff) as $sql) {
            TypeQueriesStack::addUsageQuery($sql, $definition->name);
            $event->addSql($sql);
        }

        /** Disables adding this column during CREATE TABLE query */
        $event->preventDefault();
        /** Disables additional ALTER TABLE for comment because comments always altering separately */
        $column->setComment(null);
    }

    public function onSchemaAlterTableAddColumn(SchemaAlterTableAddColumnEventArgs $event): void
    {
        if (!$event->getPlatform() instanceof PostgreSqlPlatform) {
            return;
        }

        $column = $event->getColumn();
        if (null === $definition = $this->findTypeDefinition($column)) {
            return;
        }

        foreach ($this->getEnumTypePersistenceSQL($definition) as $sql) {
            if (!TypeQueriesStack::hasPersistenceQuery($sql, $definition->name)) {
                TypeQueriesStack::addPersistenceQuery($sql, $definition->name);
                $event->addSql($sql);
            }
        }

        $platform = $event->getPlatform();
        $column->setType(GenericEnumType::create($definition->name));
        $tableDiff = new TableDiff($event->getTableDiff()->getName($platform)->getName(), [$column]);
        foreach ($this->getAlterTableColumnSQL($platform, $tableDiff) as $sql) {
            TypeQueriesStack::addUsageQuery($sql, $definition->name);
            $event->addSql($sql);
        }

        /** Disables adding this column with Doctrine */
        $event->preventDefault();
    }

    public function onSchemaAlterTableChangeColumn(SchemaAlterTableChangeColumnEventArgs $event): void
    {
        if (!$event->getPlatform() instanceof PostgreSqlPlatform) {
            return;
        }

        $diff = $event->getColumnDiff();
        $fromColumn = $diff->fromColumn;
        $column = $diff->column;

        $definition = $this->findTypeDefinition($column);
        $fromDefinition = $this->findTypeDefinition($fromColumn);

        if (null === $definition && null === $fromDefinition) {
            return;
        }

        $this->clearComment($diff);

        if ($definition?->enumClassName === $fromDefinition?->enumClassName) {
            foreach ($this->getEnumTypePersistenceSQL($definition) as $sql) {
                if (!TypeQueriesStack::hasPersistenceQuery($sql, $definition->name)) {
                    TypeQueriesStack::addPersistenceQuery($sql, $definition->name);
                    $event->addSql($sql);
                }
            }

            $platform = $event->getPlatform();
            $diff->column->setType(GenericEnumType::create($definition->name));
            $tableDiff = new TableDiff($event->getTableDiff()->getName($platform)->getName(), [], [$diff]);
            foreach ($this->getAlterTableColumnSQL($platform, $tableDiff) as $sql) {
                TypeQueriesStack::addUsageQuery($sql, $definition->name);
                $event->addSql($sql);
            }

            /** Disables altering this column with Doctrine */
            $event->preventDefault();

            return;
        }

        if (null !== $definition) {
            foreach ($this->getEnumTypePersistenceSQL($definition) as $sql) {
                if (!TypeQueriesStack::hasPersistenceQuery($sql, $definition->name)) {
                    TypeQueriesStack::addPersistenceQuery($sql, $definition->name);
                    $event->addSql($sql);
                }
            }

            $platform = $event->getPlatform();
            $diff->column->setType(GenericEnumType::create($definition->name));
            $tableDiff = new TableDiff($event->getTableDiff()->getName($platform)->getName(), [], [$diff]);
            foreach ($this->getAlterTableColumnSQL($platform, $tableDiff) as $sql) {
                $sql = preg_replace('~^ALTER TABLE [^ ]+ ALTER ([^ ]+) TYPE ([^ ]+)$~', '$0 USING $1::$2', $sql);
                TypeQueriesStack::addUsageQuery($sql, $definition->name);
                $event->addSql($sql);
            }

            /** Disables altering this column with Doctrine */
            $event->preventDefault();
        }

        if (null !== $fromDefinition) {
            $platform = $event->getPlatform();
            $tableName = $event->getTableDiff()->getName($platform)->getName();
            $columnName = $fromColumn->getName();
            foreach ($this->getEnumTypeRemovalSql($tableName, $columnName, $fromDefinition) as $sql) {
                $event->addSql($sql);
            }
        }
    }

    public function onSchemaAlterTableRemoveColumn(SchemaAlterTableRemoveColumnEventArgs $event): void
    {
        if (!$event->getPlatform() instanceof PostgreSqlPlatform) {
            return;
        }

        $column = $event->getColumn();
        if (null === $definition = $this->findTypeDefinition($column)) {
            return;
        }

        $platform = $event->getPlatform();
        $column->setType(GenericEnumType::create($definition->name));
        $tableDiff = new TableDiff($event->getTableDiff()->getName($platform)->getName(), [], [], [$column]);
        foreach ($this->getAlterTableColumnSQL($platform, $tableDiff) as $sql) {
            $event->addSql($sql);
        }

        $tableName = $event->getTableDiff()->getName($platform)->getName();
        $columnName = $column->getName();
        foreach ($this->getEnumTypeRemovalSql($tableName, $columnName, $definition) as $sql) {
            $event->addSql($sql);
        }

        /** Disables removing this column with Doctrine */
        $event->preventDefault();
    }

    /**
     * @return iterable<string>
     */
    public function getEnumTypePersistenceSql(Definition $definition): iterable
    {
        $sql = [];

        $databaseDefinition = $this->databaseRegistry->getDefinitionByName($definition->name);
        if (null === $databaseDefinition) {
            array_push($sql, ...EnumQueryBuilder::buildEnumTypeCreateSql($definition));
        } elseif (EnumChangesTool::isChanged($databaseDefinition->cases, $definition->cases)) {
            array_push($sql, ...EnumQueryBuilder::buildEnumTypeAlterSql($definition, $databaseDefinition));
        }

        return $sql;
    }

    private function clearComment(ColumnDiff $diff): void
    {
        $clearComment = CommentMarker::unmark($diff->fromColumn->getComment());
        $diff->fromColumn->setComment($clearComment);

        if ($diff->column->getComment() === $clearComment) {
            $diff->changedProperties = array_filter(array_map(
                static fn (string $item) => 'comment' !== $item ? $item : null,
                $diff->changedProperties,
            ));
        }
    }

    private function findTypeDefinition(Column $column): ?Definition
    {
        if (!$column->hasCustomSchemaOption(self::ENUM_TYPE_OPTION_NAME)) {
            return null;
        }

        return $this->registry->getDefinitionByEnum(
            $column->getCustomSchemaOption(self::ENUM_TYPE_OPTION_NAME),
        );
    }

    private function getAlterTableColumnSQL(
        AbstractPlatform $platform,
        TableDiff $tableDiff,
    ): iterable {
        $bkpEventManager = $platform->getEventManager();
        $platform->setEventManager(new EventManager());

        $sql = $platform->getAlterTableSQL($tableDiff);

        $platform->setEventManager($bkpEventManager);

        return $sql;
    }

    private function getEnumTypeRemovalSql(string $tableName, string $columnName, Definition $definition): iterable
    {
        $result = [];
        if ($this->tableUsageRegistry->isUsedElsewhereExcept($definition->name, $tableName, $columnName)) {
            foreach ($this->getEnumTypePersistenceSql($definition) as $sql) {
                if (!TypeQueriesStack::hasPersistenceQuery($sql, $definition->name)) {
                    TypeQueriesStack::addPersistenceQuery($sql, $definition->name);
                    $result[] = $sql;
                }
            }
        } else {
            foreach (EnumQueryBuilder::buildEnumTypeDropSql($definition) as $sql) {
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
