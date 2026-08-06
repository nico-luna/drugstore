<?php

declare(strict_types=1);

putenv('APP_ENV=testing');
putenv('APP_KEY=testing-key-with-more-than-thirty-two-characters');
require dirname(__DIR__) . '/app/bootstrap.php';

$tests = 0;
$failures = [];

function check(bool $condition, string $message): void
{
    global $tests, $failures;
    $tests++;
    if (!$condition) {
        $failures[] = $message;
    }
}

check(validate_password_strength('corta') !== null, 'Debe rechazar contraseñas cortas.');
check(validate_password_strength('largaperosinreglas') !== null, 'Debe exigir complejidad.');
check(validate_password_strength('ClaveLocal9!Segura') === null, 'Debe aceptar una contraseña fuerte.');
check(e('<script>') === '&lt;script&gt;', 'Debe escapar HTML.');

$schema = file_get_contents(dirname(__DIR__) . '/database/schema.sql');
check(str_contains($schema, 'FOREIGN KEY'), 'El esquema debe definir claves foráneas.');
check(!str_contains(strtolower($schema), 'md5('), 'El esquema no debe usar MD5.');
check(!preg_match('/fecha[^,]*ON UPDATE/i', $schema), 'La fecha original de venta no debe mutar en actualizaciones.');
check(str_contains($schema, 'controla_stock'), 'El esquema debe representar explícitamente el control de stock.');

$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$db->exec('PRAGMA foreign_keys = ON');
$db->exec("CREATE TABLE cliente (idcliente INTEGER PRIMARY KEY, nombre TEXT, estado INTEGER NOT NULL);
CREATE TABLE usuario (idusuario INTEGER PRIMARY KEY, nombre TEXT);
CREATE TABLE producto (codproducto INTEGER PRIMARY KEY, descripcion TEXT, precio NUMERIC, existencia INTEGER, controla_stock INTEGER, estado INTEGER);
CREATE TABLE ventas (id INTEGER PRIMARY KEY AUTOINCREMENT, id_cliente INTEGER, total NUMERIC, id_usuario INTEGER, estado TEXT DEFAULT 'confirmada', fecha TEXT DEFAULT CURRENT_TIMESTAMP, anulada_at TEXT, anulada_por INTEGER);
CREATE TABLE detalle_venta (id INTEGER PRIMARY KEY AUTOINCREMENT, id_producto INTEGER, id_venta INTEGER, cantidad INTEGER, precio NUMERIC, subtotal NUMERIC, UNIQUE(id_venta, id_producto));
INSERT INTO cliente VALUES (1, 'Público', 1);
INSERT INTO usuario VALUES (1, 'Admin');
INSERT INTO producto VALUES (1, 'Bebida', 100.00, 10, 1, 1);
INSERT INTO producto VALUES (2, 'Recarga', 50.00, 0, 0, 1);");

$service = new SaleService($db);
$saleId = $service->create(1, 1, [
    ['producto_id' => 1, 'cantidad' => 2],
    ['producto_id' => 1, 'cantidad' => 1],
    ['producto_id' => 2, 'cantidad' => 2],
]);
check($saleId === 1, 'Debe crear la primera venta.');
check((int) $db->query('SELECT existencia FROM producto WHERE codproducto = 1')->fetchColumn() === 7, 'Debe consolidar líneas y descontar stock.');
check((float) $db->query('SELECT total FROM ventas WHERE id = 1')->fetchColumn() === 400.0, 'Debe calcular el total en el servidor.');
check((int) $db->query('SELECT COUNT(*) FROM detalle_venta WHERE id_venta = 1')->fetchColumn() === 2, 'Debe guardar un detalle consolidado por producto.');

try {
    $service->create(1, 1, [['producto_id' => 1, 'cantidad' => 20]]);
    check(false, 'Debe rechazar una venta sin stock.');
} catch (InvalidArgumentException) {
    check(true, 'Rechazó correctamente el stock insuficiente.');
}
check((int) $db->query('SELECT COUNT(*) FROM ventas')->fetchColumn() === 1, 'La venta rechazada debe hacer rollback.');
check((int) $db->query('SELECT existencia FROM producto WHERE codproducto = 1')->fetchColumn() === 7, 'El rollback debe conservar el stock.');

$service->cancel(1, 1);
check($db->query('SELECT estado FROM ventas WHERE id = 1')->fetchColumn() === 'anulada', 'Debe marcar la venta como anulada.');
check((int) $db->query('SELECT existencia FROM producto WHERE codproducto = 1')->fetchColumn() === 10, 'La anulación debe restaurar el stock controlado.');

$requiredFiles = [
    'public/index.php', 'public/src/index.php', 'public/src/clientes.php', 'public/src/productos.php',
    'public/src/usuarios.php', 'public/src/nueva-venta.php', 'public/src/ventas.php', 'public/src/configuracion.php',
    'public/assets/css/app.css', 'public/assets/js/app.js',
    'public/errors/csrf.php',
];
foreach ($requiredFiles as $file) {
    check(is_file(dirname(__DIR__) . '/' . $file), 'Falta el archivo requerido: ' . $file);
}

if ($failures !== []) {
    fwrite(STDERR, "Fallaron " . count($failures) . " de $tests verificaciones:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}
fwrite(STDOUT, "OK: $tests verificaciones superadas.\n");
