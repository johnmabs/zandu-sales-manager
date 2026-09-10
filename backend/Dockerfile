FROM dunglas/frankenphp:1.12.7-php8.5-bookworm AS base

ENV APP_ENV=prod \
    APP_DEBUG=0 \
    COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /app

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/php-runtime.ini "$PHP_INI_DIR/conf.d/zz-zandu-runtime.ini"
COPY docker/Caddyfile /etc/frankenphp/Caddyfile

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
    git \
    unzip \
    && install-php-extensions \
    intl \
    pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock symfony.lock ./

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --optimize-autoloader

COPY . .

RUN composer dump-autoload \
    --no-dev \
    --classmap-authoritative
