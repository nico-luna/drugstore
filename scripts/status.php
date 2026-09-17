<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$labels = [
    'usuario' => 'users',
    'cliente' => 'clients',
    'producto' => 'products',
    'ventas' => 'sales',
];

$driver = DB::connection()->getDriverName();
if ($driver === 'mysql') {
    $tz = DB::select('SELECT @@session.time_zone as tz');
    if (!empty($tz)) {
        echo 'database_timezone=' . $tz[0]->tz . PHP_EOL;
    }
}
foreach ($labels as $table => $label) {
    echo $label . '=' . DB::table($table)->count() . PHP_EOL;
}