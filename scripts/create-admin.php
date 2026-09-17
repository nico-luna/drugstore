<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Domains\Identity\Services\AuthenticationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

$password = (string) env('DRUGSTORE_ADMIN_PASSWORD', '');
$username = (string) env('DRUGSTORE_ADMIN_USER', 'admin');
$name = (string) env('DRUGSTORE_ADMIN_NAME', 'Administrador');
$email = (string) env('DRUGSTORE_ADMIN_EMAIL', 'admin@localhost.invalid');

if ($password === '') {
    throw new RuntimeException('Definí DRUGSTORE_ADMIN_PASSWORD antes de ejecutar este comando.');
}
if ($error = AuthenticationService::validatePasswordStrength($password)) {
    throw new RuntimeException($error);
}
if (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $username)) {
    throw new RuntimeException('DRUGSTORE_ADMIN_USER no tiene un formato válido.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    throw new RuntimeException('DRUGSTORE_ADMIN_EMAIL no es válido.');
}

$exists = DB::table('usuario')
    ->where('usuario', $username)
    ->orWhere('correo', $email)
    ->count();

if ($exists > 0) {
    throw new RuntimeException('Ya existe una cuenta con ese usuario o correo. No se modificó ninguna contraseña.');
}

DB::table('usuario')->insert([
    'nombre' => $name,
    'correo' => $email,
    'usuario' => $username,
    'clave' => Hash::make($password),
    'es_admin' => 1,
    'estado' => 1,
]);

fwrite(STDOUT, 'Administrador creado: ' . $username . PHP_EOL);