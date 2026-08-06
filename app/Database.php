<?php

declare(strict_types=1);

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        if (config('db.driver') === 'sqlite') {
            $dsn = 'sqlite:' . config('db.database');
            self::$connection = new PDO($dsn);
            self::$connection->exec('PRAGMA foreign_keys = ON');
        } else {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                config('db.host'),
                config('db.port'),
                config('db.database')
            );
            self::$connection = new PDO($dsn, config('db.username'), config('db.password'));
        }

        self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        self::$connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        if (config('db.driver') !== 'sqlite') {
            $offset = (new DateTimeImmutable('now', new DateTimeZone((string) config('app.timezone'))))->format('P');
            self::$connection->prepare('SET time_zone = ?')->execute([$offset]);
        }

        return self::$connection;
    }

    public static function replaceConnection(?PDO $connection): void
    {
        self::$connection = $connection;
    }
}
