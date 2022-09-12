<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine enumerations extension for Postgres" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum\DoctrineEnum;

use Enumeum\DoctrineEnum\Exception\SimultaneousManagementTypeException;

/** TODO: This should resettable in Symfony */
class TypeQueriesStack
{
    private static array $persistenceStack = [];
    private static array $usageStack = [];
    private static array $removalStack = [];

    public static function addPersistenceQuery(string $query, string $type): void
    {
        if (!self::isRemovalStackEmpty($type)) {
            throw SimultaneousManagementTypeException::enumIsAlreadyQueuedToBeDroppedAndAttemptedToBePersisted($type);
        }

        if (self::hasPersistenceQuery($query, $type)) {
            return;
        }

        self::$persistenceStack[$type][] = $query;
    }

    public static function hasPersistenceQuery(string $query, string $type): bool
    {
        return !empty(self::$persistenceStack[$type]) && in_array($query, self::$persistenceStack[$type], true);
    }

    public static function isPersistenceStackEmpty(string $type): bool
    {
        return empty(self::$persistenceStack[$type]) || 0 === count(self::$persistenceStack[$type]);
    }

    public static function addUsageQuery(string $query, string $type): void
    {
        if (!self::isRemovalStackEmpty($type)) {
            throw SimultaneousManagementTypeException::enumIsAlreadyQueuedToBeDroppedAndAttemptedToBeUsed($type);
        }

        if (self::hasUsageQuery($query, $type)) {
            return;
        }

        self::$usageStack[$type][] = $query;
    }

    public static function hasUsageQuery(string $query, string $type): bool
    {
        return !empty(self::$usageStack[$type]) && in_array($query, self::$usageStack[$type], true);
    }

    public static function isUsageStackEmpty(string $type): bool
    {
        return empty(self::$usageStack[$type]) || 0 === count(self::$usageStack[$type]);
    }

    public static function addRemovalQuery(string $query, string $type): void
    {
        if (!self::isPersistenceStackEmpty($type)) {
            throw SimultaneousManagementTypeException::enumIsAlreadyQueuedToBePersistedAndAttemptedToBeDropped($type);
        }
        if (!self::isUsageStackEmpty($type)) {
            throw SimultaneousManagementTypeException::enumIsAlreadyQueuedToBeUsedAndAttemptedToBeDropped($type);
        }

        if (self::hasRemovalQuery($query, $type)) {
            return;
        }

        self::$removalStack[$type][] = $query;
    }

    public static function hasRemovalQuery(string $query, string $type): bool
    {
        return !empty(self::$removalStack[$type]) && in_array($query, self::$removalStack[$type], true);
    }

    public static function isRemovalStackEmpty(string $type): bool
    {
        return empty(self::$removalStack[$type]) || 0 === count(self::$removalStack[$type]);
    }

    public static function reset(): void
    {
        self::$persistenceStack = [];
        self::$usageStack = [];
        self::$removalStack = [];
    }
}
