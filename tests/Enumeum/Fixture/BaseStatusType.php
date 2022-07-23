<?php declare(strict_types=1);

namespace EnumeumTests\Fixture;

use Enumeum\DoctrineEnum\Attribute\EnumType;

#[EnumType(name: "status_type")]
enum BaseStatusType: string
{
    case STARTED = 'started';
    case PROCESSING = 'processing';
    case FINISHED = 'finished';
}
