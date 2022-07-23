<?php declare(strict_types=1);

namespace EnumeumTests\Setup;

use JetBrains\PhpStorm\ArrayShape;

abstract class BaseTestCaseSchemaPostgres13 extends BaseTestCaseSchema
{
    #[ArrayShape([
        'driver' => "string",
        'host' => "string",
        'port' => "int",
        'dbname' => "string",
        'user' => "string",
        'password' => "string",
        'charset' => "string",
        'server_version' => "string"
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
