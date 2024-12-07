FROM php:8.3-fpm-alpine as main

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

RUN install-php-extensions pdo_mysql redis

COPY . /app
WORKDIR /app

CMD ["php-fpm", "-R", "-F"]


FROM main as dev

RUN install-php-extensions pcov
RUN composer install


FROM main as prod

RUN composer install --no-dev --no-interaction --no-progress
