<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/bootstrap.php';

$password = (string) env_value('DRUGSTORE_ADMIN_PASSWORD', '');
$username = (string) env_value('DRUGSTORE_ADMIN_USER', 'admin');
$name = (string) env_value('DRUGSTORE_ADMIN_NAME', 'Administrador');
$email = (string) env_value('DRUGSTORE_ADMIN_EMAIL', 'admin@localhost.invalid');

if ($password === '') {
    throw new RuntimeException('Definí DRUGSTORE_ADMIN_PASSWORD antes de ejecutar este comando.');
}
if ($error = validate_password_strength($password)) {
    throw new RuntimeException($error);
}
if (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $username)) {
    throw new RuntimeException('DRUGSTORE_ADMIN_USER no tiene un formato válido.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    throw new RuntimeException('DRUGSTORE_ADMIN_EMAIL no es válido.');
}

$db = Database::connection();
$exists = $db->prepare('SELECT COUNT(*) FROM usuario WHERE usuario = ? OR correo = ?');
$exists->execute([$username, $email]);
if ((int) $exists->fetchColumn() > 0) {
    throw new RuntimeException('Ya existe una cuenta con ese usuario o correo. No se modificó ninguna contraseña.');
}

$statement = $db->prepare('INSERT INTO usuario (nombre, correo, usuario, clave, es_admin, estado) VALUES (?, ?, ?, ?, 1, 1)');
$statement->execute([$name, $email, $username, password_hash($password, PASSWORD_DEFAULT)]);
fwrite(STDOUT, 'Administrador creado: ' . $username . PHP_EOL);
