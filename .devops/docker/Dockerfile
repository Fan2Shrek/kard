FROM node:23-alpine AS compiler

COPY ./assets/react /app
WORKDIR /app/build

RUN npm install
RUN npm run build


FROM php:8.3-fpm-alpine AS main

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

RUN install-php-extensions pdo_mysql redis intl amqp

RUN apk add --no-cache \
    nginx \
    supervisor

COPY .devops/docker/default.conf /etc/nginx/http.d/default.conf
COPY .devops/docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

COPY . /app
WORKDIR /app

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

FROM main AS dev

RUN install-php-extensions pcov
RUN composer install --no-scripts


FROM main AS prod

ENV APP_ENV=prod

# Build env

RUN .devops/docker/build-env.sh

RUN composer install --no-dev --no-interaction --no-progress

# Build assets

COPY --from=compiler /app/dist /app/assets/react/dist

RUN bin/console sass:build
RUN bin/console asset-map:compile
