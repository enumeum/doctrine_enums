FROM php:8.1-cli-alpine

RUN apk add --no-cache git build-base libzip-dev autoconf linux-headers

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN apk add --no-cache libpq-dev
RUN docker-php-ext-install pdo pdo_pgsql pgsql

RUN pecl install xdebug && docker-php-ext-enable xdebug
COPY ./xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

ARG PGID=1000
ARG PUID=1000
RUN apk --no-cache add shadow && \
    groupmod -o -g ${PGID} www-data && \
    usermod -o -u ${PUID} -g www-data www-data

USER www-data

WORKDIR /var/www
