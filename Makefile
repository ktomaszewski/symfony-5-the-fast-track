SHELL := /bin/bash

tests:
	APP_ENV=test php bin/console doctrine:fixtures:load -n
	APP_ENV=test php bin/phpunit
.PHONY: tests

messenger:
	symfony run -d --watch=config,src,templates,vendor symfony console messenger:consume async
.PHONY: messenger
