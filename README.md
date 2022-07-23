# doctrine_enums
Doctrine enums extension

### Running Tests

To set up and run the tests, follow these steps:

- Install [Docker](https://www.docker.com/) and ensure you have `docker-compose`
- From the project root, run `docker-compose up -d --build --remove-orphans --force-recreate` to start containers in daemon mode
- Enter the container via `docker-compose exec php bash` and navigate to the root directory: `cd /var/www`
- Install Composer dependencies via `composer install`
- Run the tests: `bin/phpunit -c tests/`
