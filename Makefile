all:
	@echo "Please choose a task."
.PHONY: all

lint: lint-composer lint-php test
.PHONY: lint

lint-composer:
	composer-normalize --dry-run
	composer validate
.PHONY: lint-composer

lint-php:
	./bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php

.PHONY: lint-php

test:
	./bin/phpunit -c tests/

.PHONY: test
