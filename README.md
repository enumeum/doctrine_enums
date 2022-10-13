# doctrine_enums
Doctrine enums extension

### Running Tests

To set up and run the tests, follow these steps:

- Install [Docker](https://www.docker.com/) and ensure you have `docker-compose` and `make`
- From the project root, run `make start` to start containers in daemon mode (or using `docker-compose up -d --build --remove-orphans --force-recreate`)
- Enter the container via `make console` (or using `docker-compose exec php bash`)
- Check that you are in root directory `/var/www`, if neither then navigate using: `cd /var/www`
- Install Composer dependencies via `composer install`
- Run the tests with `make test` (or using `bin/phpunit -c tests/` inside container)
