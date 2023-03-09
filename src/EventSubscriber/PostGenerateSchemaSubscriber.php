<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum\DoctrineEnum\EventSubscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\ToolEvents;
use Enumeum\DoctrineEnum\Definition\DefinitionRegistry;
use Enumeum\DoctrineEnum\EnumUsage\TableColumnRegistry;
use Enumeum\DoctrineEnum\Type\CastingEnumeumType;
use Enumeum\DoctrineEnum\Type\EnumeumType;

class PostGenerateSchemaSubscriber implements EventSubscriber
{
    public function __construct(
        private readonly DefinitionRegistry $registry,
        private readonly TableColumnRegistry $columnRegistry,
    ) {
    }

    public function getSubscribedEvents(): array
    {
        return [
            ToolEvents::postGenerateSchema,
        ];
    }

    public function postGenerateSchema(GenerateSchemaEventArgs $event): void
    {
        if (!$event->getEntityManager()->getConnection()->getDatabasePlatform() instanceof PostgreSqlPlatform) {
            return;
        }

        foreach ($event->getSchema()->getTables() as $table) {
            foreach ($table->getColumns() as $column) {
                if (!($enumType = $this->extractEnumType($column))) {
                    continue;
                }

                if ($definition = $this->registry->getDefinitionByEnum($enumType)) {
                    $genericType = EnumeumType::create($definition->name);

                    if ($this->columnRegistry->isColumnExists($table->getName(), $column->getName())) {
                        $currentType = $this->columnRegistry->getColumnType($table->getName(), $column->getName());
                        if ($currentType !== $definition->name) {
                            $genericType = CastingEnumeumType::create($definition->name);
                            $genericType->castColumn($column->getName());
                        }
                    }

                    $column->setType($genericType);
                }
            }
        }
    }

    private function extractEnumType(Column $column): ?string
    {
        if ($column->hasPlatformOption('enumType')) {
            return $column->getPlatformOption('enumType');
        }

        if ($column->hasCustomSchemaOption('enumType')) {
            return $column->getCustomSchemaOption('enumType');
        }

        return null;
    }
}
