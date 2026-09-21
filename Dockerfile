FROM node:22-alpine AS frontend

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources ./resources
COPY vite.config.ts tsconfig.json ./
RUN npm run build

FROM composer:2 AS vendor

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

FROM php:8.4-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    libicu-dev \
    libonig-dev \
    libpq-dev \
    libsqlite3-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-install -j$(nproc) bcmath intl mbstring opcache pdo_mysql pdo_pgsql pdo_sqlite zip \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html

COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-drugstore.ini
COPY docker/security.conf /etc/apache2/conf-available/zz-drugstore-security.conf
COPY docker/entrypoint.sh /usr/local/bin/drugstore-entrypoint

RUN a2enconf zz-drugstore-security \
    && chmod +x /usr/local/bin/drugstore-entrypoint \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 10000

ENTRYPOINT ["drugstore-entrypoint"]
CMD ["apache2-foreground"]
