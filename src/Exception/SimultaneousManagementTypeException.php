<?php

declare(strict_types=1);

namespace Enumeum\DoctrineEnum\Exception;

use RuntimeException;
use function sprintf;

class SimultaneousManagementTypeException extends RuntimeException
{
    public static function enumIsAlreadyQueuedToBeDroppedAndAttemptedToBePersisted(string $type): self
    {
        return new self(sprintf(
            'Type "%s" is already queued to be dropped and then attempted to be persisted. ' .
            'SQL generating stopped. ' .
            'To avoid this exception change fields and generate migrations consequentially, one by one.', $type));
    }

    public static function enumIsAlreadyQueuedToBeDroppedAndAttemptedToBeUsed(string $type): self
    {
        return new self(sprintf(
            'Type "%s" is already queued to be dropped and then attempted to be used. ' .
            'SQL generating stopped. ' .
            'To avoid this exception change fields and generate migrations consequentially, one by one.', $type));
    }

    public static function enumIsAlreadyQueuedToBePersistedAndAttemptedToBeDropped(string $type): self
    {
        return new self(sprintf(
            'Type "%s" is already queued to be persisted and then attempted to be dropped. ' .
            'SQL generating stopped. ' .
            'To avoid this exception change fields and generate migrations consequentially, one by one.', $type));
    }

    public static function enumIsAlreadyQueuedToBeUsedAndAttemptedToBeDropped(string $type): self
    {
        return new self(sprintf(
            'Type "%s" is already queued to be used and then attempted to be dropped. ' .
            'SQL generating stopped. ' .
            'To avoid this exception change fields and generate migrations consequentially, one by one.', $type));
    }
}
