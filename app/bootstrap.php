<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/app/env.php';
load_environment_file(BASE_PATH . '/.env');
require_once BASE_PATH . '/app/config.php';
require_once BASE_PATH . '/app/Database.php';
require_once BASE_PATH . '/app/helpers.php';
require_once BASE_PATH . '/app/Auth.php';
require_once BASE_PATH . '/app/SaleService.php';
require_once BASE_PATH . '/app/views.php';

date_default_timezone_set((string) config('app.timezone'));
error_reporting(E_ALL);
ini_set('display_errors', config('app.debug') ? '1' : '0');

if (PHP_SAPI !== 'cli') {
    $sessionPath = BASE_PATH . '/storage/sessions';
    if (is_dir($sessionPath) && is_writable($sessionPath)) {
        session_save_path($sessionPath);
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('drugstore_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => (bool) config('session.secure'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}

set_exception_handler(static function (Throwable $exception): void {
    $message = sprintf("[%s] %s in %s:%d\n%s\n", date(DATE_ATOM), $exception->getMessage(), $exception->getFile(), $exception->getLine(), $exception->getTraceAsString());
    error_log($message, 3, BASE_PATH . '/storage/logs/app.log');
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $exception->getMessage() . PHP_EOL);
        exit(1);
    }
    if (http_response_code() < 400) {
        http_response_code(500);
    }
    $publicMessage = config('app.debug') ? $exception->getMessage() : 'Ocurrió un error inesperado. Revisá el registro de la aplicación.';
    require BASE_PATH . '/public/errors/500.php';
});
