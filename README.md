# Doctrine Enums
Doctrine extension to manage enumerations in PostgreSQL.

### Running Tests

To set up and run the tests, follow these steps:

- Install [Docker](https://www.docker.com/) and ensure you have `docker-compose` and `make`
- From the project root, run `make start` to start containers in daemon mode (or using `docker-compose up -d --build --remove-orphans --force-recreate`)
- Enter the container via `make console` (or using `docker-compose exec php bash`)
- Check that you are in root directory `/var/www`, if neither then navigate using: `cd /var/www`
- Install Composer dependencies via `composer install`
- Run the tests with `make test` from out of container (or using `bin/phpunit -c tests/` inside container)

### Right usage

_EnumTool is Enumeum\DoctrineEnum\EnumTool class._

If you have changed enums values, their structure, adding or dropping types then use EnumTool. It will generate SQL queries to synchronize configured enums.
After that if it is required to change not just enums but also a schema then calculate schema diff.

### Possible future feature
Command for removing Enum value without recreating.
https://postgrespro.ru/list/thread-id/2388881
