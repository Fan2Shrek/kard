WITH_DOCKER?=1
COMPOSE=$(shell which docker) compose

ifeq ($(WITH_DOCKER), 1)
	PHP=$(COMPOSE) exec php
else
	PHP=
endif

CONSOLE=$(PHP) php bin/console

.PHONY: start up vendor db fixtures cc assets assets-watch stop perm php-lint twig-lint migration sh phpstan

PHP_FIXER=$(PHP) sh -c 'PHP_CS_FIXER_IGNORE_ENV=1 vendor/bin/php-cs-fixer fix --config=./.devops/.php-cs-fixer.php'
TWIG_FIXER=$(PHP) vendor/bin/twig-cs-fixer --config=./.devops/.twig-cs-fixer.php

start: up vendor db cc assets perm

up:
	docker kill $$(docker ps -q) || true
	$(COMPOSE) build --force-rm
	$(COMPOSE) up -d --remove-orphans

stop:
	$(COMPOSE) stop

phpstan:
	$(PHP) vendor/bin/phpstan --memory-limit=-1

vendor:
	$(PHP) composer install -n
	make perm

fixtures:
	$(CONSOLE) d:f:l -n

php:
	$(EXEC) $(c)

db:
	$(CONSOLE) d:d:d --if-exists --force
	$(CONSOLE) d:d:c --if-not-exists
	$(CONSOLE) d:m:m --allow-no-migration -n
	$(MAKE) fixtures
	$(CONSOLE) app:data:init

migration:
	$(CONSOLE) d:m:m --allow-no-migration -n

cc:
	$(CONSOLE) c:cl --no-warmup
	$(CONSOLE) c:w

assets:
	rm -rf ./public/assets
	$(CONSOLE) asset-map:compile

assets-watch:
	rm -rf ./public/assets
	$(CONSOLE) sass:build --watch

perm:
	sudo chown -R $(USER):$(USER) ./
	mkdir -p ./var ./public/
	sudo chown -R www-data:$(USER) ./var ./public/
	sudo chmod -R g+rwx .

sh:
	$(PHP) sh

php-lint:
	$(PHP_FIXER)

php-lint-dry:
	$(PHP_FIXER) --dry-run

twig-lint:
	$(TWIG_FIXER) --fix

twig-lint-dry:
	$(TWIG_FIXER) --report=github
