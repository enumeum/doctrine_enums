<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine enumerations extension for Postgres" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum\DoctrineEnum;

use Enumeum\DoctrineEnum\Definition\DatabaseDefinition;
use Enumeum\DoctrineEnum\Definition\DatabaseDefinitionRegistry;
use Enumeum\DoctrineEnum\Definition\Definition;
use Enumeum\DoctrineEnum\Tools\EnumChangesTool;
use function implode;
use function sprintf;

class EnumQueriesGenerator
{
    private const TYPE_CREATE_QUERY = "CREATE TYPE %1\$s AS ENUM ('%2\$s')";
    private const TYPE_ALTER_QUERY = "ALTER TYPE %1\$s ADD VALUE IF NOT EXISTS '%2\$s'";
    private const TYPE_DROP_QUERY = 'DROP TYPE %1$s';

    public function __construct(
        private readonly DatabaseDefinitionRegistry $registry,
    ) {
    }

    /**
     * @return iterable<string>
     */
    public function generateEnumTypePersistenceSql(Definition $definition): iterable
    {
        $sql = [];

        $databaseDefinition = $this->registry->getTypeDefinitionByName($definition->name);
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

    public function generateEnumTypeDropSql(Definition $definition): iterable
    {
        return [sprintf(self::TYPE_DROP_QUERY, $definition->name)];
    }

    public function generateEnumTypeDropSqlFromDatabaseDefinition(DatabaseDefinition $definition): iterable
    {
        return [sprintf(self::TYPE_DROP_QUERY, $definition->name)];
    }
}
