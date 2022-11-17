<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EnumeumTests\Fixture\EnumToolType;

use Enumeum\DoctrineEnum\Attribute\EnumType;

#[EnumType(name: 'reorder_status_type')]
enum ReorderStatusType: string
{
    case STARTED = 'started';
    case FINISHED = 'finished';
}
