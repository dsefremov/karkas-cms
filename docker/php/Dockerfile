# syntax=docker/dockerfile:1
ARG PHP_VERSION=8.4
FROM php:${PHP_VERSION}-apache-bookworm

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libicu-dev libzip-dev libpng-dev libonig-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring intl fileinfo opcache zip gd \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Shared-style limits (CLI and Apache). disable_functions applied only in Apache vhost (web SAPI).
RUN { \
        echo 'expose_php = Off'; \
        echo 'memory_limit = 256M'; \
        echo 'max_execution_time = 120'; \
    } > /usr/local/etc/php/conf.d/karkas-shared.ini

COPY docker/php/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
