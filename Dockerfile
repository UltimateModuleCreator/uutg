# syntax=docker/dockerfile:1

FROM composer:lts as deps
WORKDIR /app

FROM php:8.2-apache as final
RUN docker-php-ext-install pdo pdo_mysql
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY ./src /var/www/html
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
USER www-data

