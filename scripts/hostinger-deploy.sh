#!/usr/bin/env sh
set -eu

PHP_BIN="${PHP_BIN:-/opt/alt/php84/usr/bin/php}"
COMPOSER_BIN="${COMPOSER_BIN:-/usr/local/bin/composer2}"

if [ ! -f .env ]; then
    echo "Falta .env. Copiá .env.hostinger.example y completá sus valores." >&2
    exit 1
fi

mkdir -p bootstrap/cache storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs
chmod -R ug+rwX bootstrap/cache storage

"$PHP_BIN" "$COMPOSER_BIN" install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

"$PHP_BIN" artisan optimize:clear

if ! grep -q '^APP_KEY=base64:' .env; then
    "$PHP_BIN" artisan key:generate --force
fi

"$PHP_BIN" artisan migrate --force

if grep -Eq '^DRUGSTORE_ADMIN_PASSWORD=.+$' .env; then
    "$PHP_BIN" scripts/create-admin.php
fi

"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan view:cache

echo "Deploy de Hostinger completado. Verificá /up y luego iniciá sesión."
