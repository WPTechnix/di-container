# Development image with PHP, Composer, Node.js, and npm.
FROM php:8.3-cli-alpine

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer

RUN apk add --no-cache \
        bash \
        git \
        unzip \
        zip \
        curl \
        nodejs \
        npm \
        $PHPIZE_DEPS \
    && docker-php-source extract \
    && docker-php-source delete \
    && apk del $PHPIZE_DEPS

# Copy Composer from the official image.
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app

CMD ["php", "-v"]
