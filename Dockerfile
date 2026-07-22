ARG PHP_VERSION=8.3

FROM php:${PHP_VERSION}-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

CMD ["sh", "-c", "composer install --no-interaction --prefer-dist && vendor/bin/pest"]
