<?php declare(strict_types=1);

namespace EnumeumTests\Fixture;

use Enumeum\DoctrineEnum\Attribute\EnumType;

#[EnumType(name: "status_type")]
enum AddedValuesStatusType: string
{
    case STARTED = 'started';
    case PROCESSING = 'processing';
    case FINISHED = 'finished';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
}
