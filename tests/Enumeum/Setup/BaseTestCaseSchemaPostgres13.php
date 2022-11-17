<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EnumeumTests\Setup;

use JetBrains\PhpStorm\ArrayShape;

abstract class BaseTestCaseSchemaPostgres13 extends BaseTestCaseSchema
{
    #[ArrayShape([
        'driver' => 'string',
        'host' => 'string',
        'port' => 'int',
        'dbname' => 'string',
        'user' => 'string',
        'password' => 'string',
        'charset' => 'string',
        'server_version' => 'string',
    ])]
    protected function getConnectionParams(): array
    {
        return [
            'driver' => 'pdo_pgsql',
            'host' => 'enum_pgsql',
            'port' => 5432,
            'dbname' => 'enum_testing',
            'user' => 'enum_user',
            'password' => 'enum_password',
            'charset' => 'UTF8',
            'server_version' => '13',
        ];
    }
}
