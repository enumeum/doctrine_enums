<?php declare(strict_types=1);

namespace EnumeumTests\Fixture;

use Enumeum\DoctrineEnum\Attribute\EnumType;

#[EnumType(name: "status_type")]
enum RemovedValuesStatusType: string
{
    case STARTED = 'started';
    case FINISHED = 'finished';
}
