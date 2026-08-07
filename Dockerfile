FROM php:8.3-apache

RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo_mysql pdo_sqlite bcmath opcache \
    && a2enmod rewrite headers

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

COPY . /var/www/html
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-drugstore.ini
COPY docker/security.conf /etc/apache2/conf-available/zz-drugstore-security.conf

RUN a2enconf zz-drugstore-security \
    && chown -R www-data:www-data /var/www/html/storage
