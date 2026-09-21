#!/bin/sh
set -eu

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY es obligatorio." >&2
    exit 1
fi

case "$APP_KEY" in
    base64:*) ;;
    *)
        APP_KEY="base64:$(printf '%s' "$APP_KEY" | openssl dgst -sha256 -binary | base64)"
        export APP_KEY
        ;;
esac

port="${PORT:-80}"
sed -ri "s!Listen [0-9]+!Listen ${port}!" /etc/apache2/ports.conf
sed -ri "s!<VirtualHost \*:[0-9]+>!<VirtualHost *:${port}>!" /etc/apache2/sites-available/*.conf

php artisan package:discover --ansi
php artisan config:cache --ansi
php artisan view:cache --ansi
php artisan migrate --force --ansi

if [ -n "${DRUGSTORE_ADMIN_PASSWORD:-}" ]; then
    php scripts/create-admin.php
fi

exec "$@"
