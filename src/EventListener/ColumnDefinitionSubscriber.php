<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine enumerations extension for Postgres" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum\DoctrineEnum\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Event\SchemaColumnDefinitionEventArgs;
use Doctrine\DBAL\Events;
use Doctrine\DBAL\Schema\Column;
use Enumeum\DoctrineEnum\Definition\DatabaseDefinitionRegistry;
use Enumeum\DoctrineEnum\Definition\Definition;
use Enumeum\DoctrineEnum\Definition\DefinitionRegistry;
use Enumeum\DoctrineEnum\Tools\CommentMarker;
use Enumeum\DoctrineEnum\Tools\EnumChangesTool;
use Enumeum\DoctrineEnum\Type\EnumeumType;

class ColumnDefinitionSubscriber implements EventSubscriber
{
    public function __construct(
        private readonly DefinitionRegistry $definitionRegistry,
        private readonly DatabaseDefinitionRegistry $databaseDefinitionRegistry,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        return [
            Events::onSchemaColumnDefinition,
        ];
    }

    public function onSchemaColumnDefinition(SchemaColumnDefinitionEventArgs $event): void
    {
        $tableColumn = $event->getTableColumn();
        $definition = $this->definitionRegistry->getDefinitionByType($tableColumn['type']);
        if (null === $definition) {
            return;
        }

        $event->preventDefault();
        $event->setColumn($this->createSchemaColumn($tableColumn, $definition));
    }

    private function createSchemaColumn(array $tableColumn, Definition $definition): Column
    {
        $databaseDefinition = $this->databaseDefinitionRegistry->getTypeDefinition($tableColumn['type']);

        return (new Column(
            $tableColumn['field'],
            new EnumeumType(),
        ))
            ->setComment(
                EnumChangesTool::isChanged($databaseDefinition->cases, $definition->cases)
                    ? CommentMarker::mark($tableColumn['comment'])
                    : $tableColumn['comment']
            )
            ->setCustomSchemaOption(SchemaChangedSubscriber::ENUM_TYPE_OPTION_NAME, $definition->enumClassName)
        ;
    }
}
