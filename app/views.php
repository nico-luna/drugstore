<?php

declare(strict_types=1);

function render_header(string $title): void
{
    $user = Auth::user();
    $success = flash('success');
    $error = flash('error');
    ?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title><?= e($title) ?> · Drugstore</title>
    <link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>">
    <script src="<?= e(url('assets/js/app.js')) ?>" defer></script>
</head>
<body>
<header class="topbar">
    <a class="brand" href="<?= e(url('src/index.php')) ?>">Drugstore</a>
    <button class="nav-toggle" type="button" aria-controls="main-nav" aria-expanded="false">Menú</button>
    <nav id="main-nav" class="main-nav" aria-label="Navegación principal">
        <a href="<?= e(url('src/index.php')) ?>">Inicio</a>
        <?php if (Auth::can('clientes')): ?><a href="<?= e(url('src/clientes.php')) ?>">Clientes</a><?php endif; ?>
        <?php if (Auth::can('productos')): ?><a href="<?= e(url('src/productos.php')) ?>">Productos</a><?php endif; ?>
        <?php if (Auth::can('nueva_venta')): ?><a href="<?= e(url('src/nueva-venta.php')) ?>">Nueva venta</a><?php endif; ?>
        <?php if (Auth::can('ventas')): ?><a href="<?= e(url('src/ventas.php')) ?>">Ventas</a><?php endif; ?>
        <?php if (Auth::can('usuarios')): ?><a href="<?= e(url('src/usuarios.php')) ?>">Usuarios</a><?php endif; ?>
        <?php if (Auth::can('configuracion')): ?><a href="<?= e(url('src/configuracion.php')) ?>">Configuración</a><?php endif; ?>
    </nav>
    <div class="account">
        <span><?= e($user['nombre'] ?? '') ?></span>
        <form method="post" action="<?= e(url('src/logout.php')) ?>">
            <?= csrf_field() ?>
            <button class="link-button" type="submit">Salir</button>
        </form>
    </div>
</header>
<main class="page-shell">
    <div class="page-heading">
        <h1><?= e($title) ?></h1>
    </div>
    <?php if ($success): ?><div class="alert success" role="status"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert error" role="alert"><?= e($error) ?></div><?php endif; ?>
    <?php
}

function render_footer(): void
{
    ?>
</main>
<footer class="footer">Sistema de gestión Drugstore</footer>
</body>
</html>
    <?php
}

function render_validation_errors(array $errors): void
{
    if ($errors === []) {
        return;
    }
    ?>
    <div class="alert error" role="alert">
        <strong>Revisá los datos:</strong>
        <ul>
            <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
        </ul>
    </div>
    <?php
}

function status_badge(bool $active): string
{
    return '<span class="badge ' . ($active ? 'active' : 'inactive') . '">' . ($active ? 'Activo' : 'Inactivo') . '</span>';
}
