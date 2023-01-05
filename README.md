# Doctrine Enums Extension
This package contains an extension for Doctrine ORM and DBAL which offer enum management functionality in a PostgreSQL database.
Yes, enumerations in DB is bad practice, and you should avoid to use them. But sometimes DB management needs Enums as a simple constraint on certain fields.
This package provides a transparent approach to adding PHP enums as database types and using them with appropriate fields in Doctrine entities.


## Requirements
Minimum PHP version is 8.1.


## Installation
    composer require enumeum/doctrine-enums


## Usage
### Enumeum attribute for PHP enum:
- **#[Enumeum\DoctrineEnum\Attribute\EnumType(name: 'type_name')]** this attribute tells that this enum is database type.
  By default, it creates type in database with its own cases.

### Example

```php
<?php
namespace App\Enums;

use Enumeum\DoctrineEnum\Attribute\EnumType;

#[EnumType(name: 'status_type')]
enum StatusType: string
{
    case STARTED = 'started';
    case PROCESSING = 'processing';
    case FINISHED = 'finished';
}
```

```php
<?php
namespace App\Entities;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\StatusType;

/**
 * @ORM\Entity
 * @ORM\Table(name="entity")
 */
#[ORM\Entity]
#[ORM\Table(name: 'entity')]
class Entity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    private int $id;

    /**
     * @ORM\Column(type="string", enumType=StatusType::class, options={"comment":"SOME Comment"})
     */
    #[ORM\Column(type: Types::STRING, enumType: StatusType::class, options: ['comment' => 'SOME Comment'])]
    private StatusType $status;

    public function __construct(
        int $id,
        StatusType $status,
    ) {
        $this->id = $id;
        $this->status = $status;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getStatus(): StatusType
    {
        return $this->status;
    }
}
```

Please note that the configuration of the entity is no different from the usual one. Doctrine supports "enumType" property and converts it transparently.

### DBAL Configuration
```php
<?php

namespace App;

use Enumeum\DoctrineEnum\Definition\DefinitionRegistryLoader;
use Enums\BaseStatusType;

$enumClassNames = [
    BaseStatusType::class,
    // ...
];

$enumDirPaths = [
    [
        'path' => __DIR__.'/../Enums',
        'namespace' => 'App\Enums',
    ]
    // ...
];

$loader = DefinitionRegistryLoader::create(new EnumClassLocator([]), $enumClassNames, $enumDirPaths);
```

Additional types or directories can be added by the appropriate methods one or more
```php

$loader->loadType(BaseStatusType::class);
$loader->loadTypes([BaseStatusType::class]);

$loader->loadDir([
    'path' => __DIR__.'/../Enums',
    'namespace' : 'App\Enums',
]);
$loader->loadDirs([
    [
        'path' => __DIR__.'/../Enums',
        'namespace' : 'App\Enums',
    ],
]);
```

Filled loader provides Enumeum\DoctrineEnum\Definition\DefinitionRegistry instance with collection of enums definitions.
```php
// Every call creates new Registry instance.
$registry = $loader->getRegistry();
```

Next step is to create Enumeum\DoctrineEnum\EnumTool and use it to generate SQL queries or update Database directly.
```php
<?php

namespace App;

use Enumeum\DoctrineEnum\EnumTool;

$tool = EnumTool::create($registry, $doctrineDbalConnection);

$sql = $tool->getCreateSchemaSql();
// ...
```

## Usage

If you have changed enums values, their structure, adding or dropping types then use EnumTool.
It will generate SQL queries to synchronize configured enums.
After that if it is required to change not just enums but also a schema then calculate schema diff.


## Running Tests

To set up and run the tests, follow these steps:

- Install [Docker](https://www.docker.com/) and ensure you have `docker-compose` and `make` (optional)
- From the project root, run `make start` to start containers in daemon mode (or using `docker-compose up -d --build --remove-orphans --force-recreate`)
- Enter the container via `make console` (or using `docker-compose exec php bash`)
- Check that you are in root directory `/var/www`, if neither then navigate using: `cd /var/www`
- Install Composer dependencies via `composer install`
- Run the tests with `make test` from out of container (or using `bin/phpunit -c tests/` inside container)


## Possible future feature
Command for removing Enum value without recreating.
https://postgrespro.ru/list/thread-id/2388881
