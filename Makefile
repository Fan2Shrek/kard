COMPOSE=docker compose
EXEC=$(COMPOSE) exec php
CONSOLE=$(EXEC) php bin/console

.PHONY: start up composer db fixtures cc stop rm perm php-lint twig-lint

start: up composer db cc perm

up:
	docker kill $$(docker ps -q) || true
	$(COMPOSE) build --force-rm
	$(COMPOSE) up -d --remove-orphans

stop:
	$(COMPOSE) stop

rm:
	make stop
	$(COMPOSE) rm

composer:
	$(EXEC) composer install -n
	make perm

php:
	$(EXEC) $(c)

db:
	$(CONSOLE) d:d:d --if-exists --force
	$(CONSOLE) d:d:c --if-not-exists
	$(CONSOLE) d:s:u --force --complete
	$(CONSOLE) d:f:l -n

cc:
	$(CONSOLE) c:cl --no-warmup
	$(CONSOLE) c:w

assets:
	rm -rf ./public/assets
	$(CONSOLE) asset-map:compile

perm:
	sudo chown -R $(USER):$(USER) .
	mkdir -p ./var ./public/
	sudo chown -R www-data:$(USER) ./var ./public/
	sudo chmod -R g+rwx .

php-lint:
	sh -c "COMPOSE_INTERACTIVE_NO_CLI=1 $(EXEC) vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php"

twig-lint:
	sh -c "COMPOSE_INTERACTIVE_NO_CLI=1 $(EXEC) vendor/bin/twig-cs-fixer lint --fix --config=.twig-cs-fixer.php"
