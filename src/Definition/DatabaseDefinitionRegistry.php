<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine enumerations extension for Postgres" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum\DoctrineEnum\Definition;

use Doctrine\DBAL\Connection;

class DatabaseDefinitionRegistry
{
    private const TYPES_QUERY = <<<QUERY
SELECT
    pg_type.typname as name,
    pg_enum.enumlabel as value,
    pg_enum.enumsortorder as "order"
FROM
    pg_type 
JOIN
    pg_enum ON pg_enum.enumtypid = pg_type.oid;
QUERY;

    private bool $loaded = false;

    /** @var DatabaseDefinition[] */
    private array $definitions;

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function getDefinitionByName(string $name): ?DatabaseDefinition
    {
        if (!$this->loaded) {
            $this->loadDefinitions();
        }

        return $this->definitions[$name] ?? null;
    }

    /**
     * @return array<DatabaseDefinition>
     */
    public function getDefinitionsHashedByName(): array
    {
        if (!$this->loaded) {
            $this->loadDefinitions();
        }

        return $this->definitions;
    }

    private function loadDefinitions(): void
    {
        $values = $this->connection->executeQuery(self::TYPES_QUERY)->fetchAllAssociative();

        $sorted = [];
        foreach ($values as $value) {
            $sorted[$value['name']][] = $value;
        }

        foreach ($sorted as $name => $type) {
            usort($type, static fn (array $a, array $b) => $a['order'] > $b['order'] ? 1 : -1);
            $this->definitions[$name] = new DatabaseDefinition(
                $name,
                array_map(static fn (array $a) => $a['value'], $type),
            );
        }

        $this->loaded = true;
    }
}
