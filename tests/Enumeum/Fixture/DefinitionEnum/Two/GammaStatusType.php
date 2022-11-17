<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EnumeumTests\Fixture\DefinitionEnum\Two;

use Enumeum\DoctrineEnum\Attribute\EnumType;

#[EnumType(name: 'gamma_status_type_two')]
enum GammaStatusType: string
{
    case STARTED = 'started';
    case PROCESSING = 'processing';
    case FINISHED = 'finished';
}
