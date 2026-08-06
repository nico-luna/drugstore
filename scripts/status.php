<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/bootstrap.php';

$db = Database::connection();
$labels = [
    'usuario' => 'users',
    'cliente' => 'clients',
    'producto' => 'products',
    'ventas' => 'sales',
];

echo 'database_timezone=' . $db->query('SELECT @@session.time_zone')->fetchColumn() . PHP_EOL;
foreach ($labels as $table => $label) {
    echo $label . '=' . $db->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn() . PHP_EOL;
}
