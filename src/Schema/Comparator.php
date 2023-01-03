<?php

declare(strict_types=1);

namespace Enumeum\DoctrineEnum\Schema;

use Enumeum\DoctrineEnum\Definition\Definition;
use Enumeum\DoctrineEnum\Tools\EnumCasesTool;

class Comparator
{
    public static function create(): self
    {
        return new self();
    }

    public function compareSchemas(Schema $fromSchema, Schema $toSchema): SchemaDiff
    {
        return new SchemaDiff(
            $this->collectCreateChangeSet($fromSchema, $toSchema),
            $this->collectAlterChangeSet($fromSchema, $toSchema),
            $this->collectDropChangeSet($fromSchema, $toSchema),
        );
    }

    /**
     * @return iterable<Definition>
     */
    private function collectCreateChangeSet(Schema $fromSchema, Schema $toSchema): iterable
    {
        $creating = [];
        foreach ($toSchema->getDefinitions() as $name => $definition) {
            if (!$fromSchema->hasDefinition($name)) {
                $creating[] = $definition;
            }
        }

        return $creating;
    }

    /**
     * @return iterable<DefinitionDiff>
     */
    private function collectAlterChangeSet(Schema $fromSchema, Schema $toSchema): iterable
    {
        $altering = [];
        foreach ($fromSchema->getDefinitions() as $name => $definition) {
            if (!$toSchema->hasDefinition($name)) {
                continue;
            }

            $targetDefinition = $toSchema->getDefinition($name);
            if (EnumCasesTool::isChanged($definition->cases, $targetDefinition->cases)) {
                $altering[] = new DefinitionDiff(
                    $definition,
                    $targetDefinition,
                    $fromSchema->getUsage($name),
                );
            }
        }

        return $altering;
    }

    /**
     * @return iterable<Definition>
     */
    private function collectDropChangeSet(Schema $fromSchema, Schema $toSchema): array
    {
        $dropping = [];
        foreach ($fromSchema->getDefinitions() as $name => $definition) {
            if (!$toSchema->hasDefinition($name)) {
                $dropping[] = $definition;
            }
        }

        return $dropping;
    }
}
