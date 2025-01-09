#!/bin/bash

docker compose up -d

# install php
docker compose exec php composer install

sleep 7

docker compose exec php bin/console doctrine:database:create --if-not-exists
docker compose exec php bin/console doctrine:migration:migrate -n --allow-no-migration
docker compose exec php bin/console doctrine:fixtures:load --no-interaction
docker compose exec php bin/console app:data:init

docker compose exec php bin/console sass:build
