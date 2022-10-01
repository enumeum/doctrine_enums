<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine enumerations extension for Postgres" package.
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
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\TableDiff;
use Enumeum\DoctrineEnum\Definition\DatabaseDefinitionRegistry;
use Enumeum\DoctrineEnum\Definition\Definition;
use Enumeum\DoctrineEnum\Definition\DefinitionRegistry;
use Enumeum\DoctrineEnum\EnumUsage\UsageRegistry;
use Enumeum\DoctrineEnum\Tools\CommentMarker;
use Enumeum\DoctrineEnum\Tools\EnumChangesTool;
use Enumeum\DoctrineEnum\Type\GenericEnumType;
use Enumeum\DoctrineEnum\TypeQueriesStack;

class SchemaChangedSubscriber implements EventSubscriber
{
    public const ENUM_TYPE_OPTION_NAME = 'enumType';
    private const TYPE_CREATE_QUERY = "CREATE TYPE %1\$s AS ENUM ('%2\$s')";
    private const TYPE_ALTER_QUERY = "ALTER TYPE %1\$s ADD VALUE IF NOT EXISTS '%2\$s'";
    private const TYPE_DROP_QUERY = 'DROP TYPE %1$s';

    public function __construct(
        private readonly DefinitionRegistry $definitionRegistry,
        private readonly DatabaseDefinitionRegistry $databaseDefinitionRegistry,
        private readonly UsageRegistry $usageRegistry,
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
        $column = $event->getColumn();
        if (null === $definition = $this->findTypeDefinition($column)) {
            return;
        }

        foreach ($this->generateEnumTypePersistenceSQL($definition) as $sql) {
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
        $column = $event->getColumn();
        if (null === $definition = $this->findTypeDefinition($column)) {
            return;
        }

        foreach ($this->generateEnumTypePersistenceSQL($definition) as $sql) {
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
            foreach ($this->generateEnumTypePersistenceSQL($definition) as $sql) {
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
            foreach ($this->generateEnumTypePersistenceSQL($definition) as $sql) {
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
            foreach ($this->generateEnumTypeRemovalSQL($tableName, $fromDefinition, $fromColumn) as $sql) {
                $event->addSql($sql);
            }
        }
    }

    public function onSchemaAlterTableRemoveColumn(SchemaAlterTableRemoveColumnEventArgs $event): void
    {
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
        foreach ($this->generateEnumTypeRemovalSQL($tableName, $definition, $column) as $sql) {
            $event->addSql($sql);
        }

        /** Disables removing this column with Doctrine */
        $event->preventDefault();
    }

    /**
     * @return iterable<string>
     */
    private function generateEnumTypePersistenceSQL(Definition $definition): iterable
    {
        $sql = [];

        $databaseDefinition = $this->databaseDefinitionRegistry->getTypeDefinition($definition->name);
        if (null === $databaseDefinition) {
            $sql[] = sprintf(
                self::TYPE_CREATE_QUERY,
                $definition->name,
                implode("', '", [...$definition->cases]),
            );
        } elseif (EnumChangesTool::isChanged($databaseDefinition->cases, $definition->cases)) {
            $add = EnumChangesTool::getAlterAddValues($databaseDefinition->cases, $definition->cases);
            foreach ($add as $value) {
                $sql[] = sprintf(
                    self::TYPE_ALTER_QUERY,
                    $definition->name,
                    $value,
                );
            }
        }

        return $sql;
    }

    private function generateEnumTypeRemovalSQL(string $tableName, Definition $definition, Column $column): iterable
    {
        $result = [];
        if ($this->usageRegistry->isUsedElsewhereExcept($definition->name, $tableName, $column->getName())) {
            foreach ($this->generateEnumTypePersistenceSQL($definition) as $sql) {
                if (!TypeQueriesStack::hasPersistenceQuery($sql, $definition->name)) {
                    TypeQueriesStack::addPersistenceQuery($sql, $definition->name);
                    $result[] = $sql;
                }
            }
        } else {
            // dump('NOT USED!!!');
            $sql = sprintf(self::TYPE_DROP_QUERY, $definition->name);
            if (!TypeQueriesStack::hasRemovalQuery($sql, $definition->name)
                && TypeQueriesStack::isPersistenceStackEmpty($definition->name)
                && TypeQueriesStack::isUsageStackEmpty($definition->name)
            ) {
                TypeQueriesStack::addRemovalQuery($sql, $definition->name);
                $result[] = $sql;
            }
        }

        return $result;
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

        return $this->definitionRegistry->getDefinitionByEnum(
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
}
