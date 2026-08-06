<?php

declare(strict_types=1);

function config(string $key, mixed $default = null): mixed
{
    static $values;

    if ($values === null) {
        $values = [
            'app.env' => env_value('APP_ENV', 'production'),
            'app.debug' => (bool) env_value('APP_DEBUG', false),
            'app.key' => (string) env_value('APP_KEY', ''),
            'app.url' => rtrim((string) env_value('APP_URL', ''), '/'),
            'app.timezone' => (string) env_value('APP_TIMEZONE', 'America/Argentina/Buenos_Aires'),
            'session.secure' => (bool) env_value('SESSION_SECURE_COOKIE', false),
            'db.driver' => (string) env_value('DB_DRIVER', 'mysql'),
            'db.host' => (string) env_value('DB_HOST', '127.0.0.1'),
            'db.port' => (int) env_value('DB_PORT', 3306),
            'db.database' => (string) env_value('DB_DATABASE', 'drugstore'),
            'db.username' => (string) env_value('DB_USERNAME', 'drugstore'),
            'db.password' => (string) env_value('DB_PASSWORD', ''),
        ];
    }

    return $values[$key] ?? $default;
}
