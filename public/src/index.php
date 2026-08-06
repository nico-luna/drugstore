<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';
Auth::requireLogin();

$db = Database::connection();
$cards = [];
if (Auth::can('clientes')) {
    $cards[] = ['Clientes activos', (int) $db->query('SELECT COUNT(*) FROM cliente WHERE estado = 1')->fetchColumn(), 'clientes.php'];
}
if (Auth::can('productos')) {
    $cards[] = ['Productos activos', (int) $db->query('SELECT COUNT(*) FROM producto WHERE estado = 1')->fetchColumn(), 'productos.php'];
}
if (Auth::can('ventas')) {
    $cards[] = ['Ventas de hoy', (int) $db->query("SELECT COUNT(*) FROM ventas WHERE estado = 'confirmada' AND DATE(fecha) = CURRENT_DATE")->fetchColumn(), 'ventas.php'];
    $cards[] = ['Total de hoy', money((float) $db->query("SELECT COALESCE(SUM(total), 0) FROM ventas WHERE estado = 'confirmada' AND DATE(fecha) = CURRENT_DATE")->fetchColumn()), 'ventas.php'];
}

render_header('Panel principal');
?>
<section class="hero-panel">
    <div>
        <p class="eyebrow">Resumen operativo</p>
        <h2>Hola, <?= e(Auth::user()['nombre']) ?></h2>
        <p>Gestioná la operación diaria desde un único lugar.</p>
    </div>
    <?php if (Auth::can('nueva_venta')): ?><a class="button primary" href="<?= e(url('src/nueva-venta.php')) ?>">Registrar venta</a><?php endif; ?>
</section>
<section class="stats-grid" aria-label="Indicadores">
    <?php foreach ($cards as [$label, $value, $target]): ?>
        <a class="stat-card" href="<?= e(url('src/' . $target)) ?>">
            <span><?= e($label) ?></span>
            <strong><?= e($value) ?></strong>
        </a>
    <?php endforeach; ?>
</section>
<?php render_footer(); ?>
