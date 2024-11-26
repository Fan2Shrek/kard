FROM php:8.3-alpine

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

RUN install-php-extensions pdo_mysql redis pcov

CMD ["php", "-S", "0.0.0.0:80", "public/index.php"]
