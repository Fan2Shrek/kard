#!/bin/bash

docker compose up -d

# install php 
docker compose exec php composer install

sleep 7

docker compose exec php bin/console doctrine:database:create --if-not-exists
docker compose exec php bin/console doctrine:schema:update --force
docker compose exec php bin/console doctrine:fixtures:load --no-interaction

docker compose exec php bin/console sass:build
