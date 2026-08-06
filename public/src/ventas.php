<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';
Auth::requirePermission('ventas');
$db = Database::connection();
$errors = [];

if (is_post()) {
    verify_csrf();
    if (post_string('action') === 'cancel') {
        try {
            (new SaleService($db))->cancel((int) ($_POST['id'] ?? 0), (int) Auth::user()['idusuario']);
            flash('success', 'Venta anulada y stock restaurado.');
        } catch (InvalidArgumentException|RuntimeException $exception) {
            flash('error', $exception->getMessage());
        }
        redirect('src/ventas.php?view=' . (int) ($_POST['id'] ?? 0));
    }
}

$viewId = request_int('view');
$saleDetail = null;
$detailLines = [];
if ($viewId > 0) {
    $statement = $db->prepare(
        'SELECT v.*, c.nombre AS cliente, u.nombre AS vendedor, au.nombre AS anulada_por_nombre
         FROM ventas v
         JOIN cliente c ON c.idcliente = v.id_cliente
         JOIN usuario u ON u.idusuario = v.id_usuario
         LEFT JOIN usuario au ON au.idusuario = v.anulada_por
         WHERE v.id = ?'
    );
    $statement->execute([$viewId]);
    $saleDetail = $statement->fetch() ?: null;
    if ($saleDetail) {
        $statement = $db->prepare(
            'SELECT dv.*, p.codigo, p.descripcion FROM detalle_venta dv
             JOIN producto p ON p.codproducto = dv.id_producto WHERE dv.id_venta = ? ORDER BY dv.id'
        );
        $statement->execute([$viewId]);
        $detailLines = $statement->fetchAll();
    }
}

$from = trim((string) ($_GET['desde'] ?? date('Y-m-01')));
$to = trim((string) ($_GET['hasta'] ?? date('Y-m-d')));
$datePattern = '/^\d{4}-\d{2}-\d{2}$/';
if (!preg_match($datePattern, $from)) {
    $from = date('Y-m-01');
}
if (!preg_match($datePattern, $to)) {
    $to = date('Y-m-d');
}
$statement = $db->prepare(
    'SELECT v.id, v.total, v.estado, v.fecha, c.nombre AS cliente, u.nombre AS vendedor
     FROM ventas v JOIN cliente c ON c.idcliente = v.id_cliente JOIN usuario u ON u.idusuario = v.id_usuario
     WHERE v.fecha >= ? AND v.fecha < DATE_ADD(?, INTERVAL 1 DAY)
     ORDER BY v.fecha DESC LIMIT 500'
);
$statement->execute([$from, $to]);
$sales = $statement->fetchAll();

render_header('Ventas');
?>
<?php if ($saleDetail): ?>
<section class="panel detail-panel">
    <div class="panel-toolbar"><div><p class="eyebrow">Comprobante</p><h2>Venta #<?= e($saleDetail['id']) ?></h2></div><span class="badge <?= $saleDetail['estado'] === 'confirmada' ? 'active' : 'inactive' ?>"><?= e(ucfirst($saleDetail['estado'])) ?></span></div>
    <dl class="detail-grid"><div><dt>Fecha</dt><dd><?= e(date('d/m/Y H:i', strtotime($saleDetail['fecha']))) ?></dd></div><div><dt>Cliente</dt><dd><?= e($saleDetail['cliente']) ?></dd></div><div><dt>Vendedor</dt><dd><?= e($saleDetail['vendedor']) ?></dd></div><?php if ($saleDetail['anulada_at']): ?><div><dt>Anulada</dt><dd><?= e(date('d/m/Y H:i', strtotime($saleDetail['anulada_at']))) ?> por <?= e($saleDetail['anulada_por_nombre']) ?></dd></div><?php endif; ?></dl>
    <div class="table-scroll"><table><thead><tr><th>Producto</th><th>Cantidad</th><th>Precio</th><th>Subtotal</th></tr></thead><tbody><?php foreach ($detailLines as $line): ?><tr><td><?= e($line['codigo'] . ' · ' . $line['descripcion']) ?></td><td><?= e($line['cantidad']) ?></td><td><?= e(money($line['precio'])) ?></td><td><?= e(money($line['subtotal'])) ?></td></tr><?php endforeach; ?></tbody><tfoot><tr><th colspan="3">Total</th><th><?= e(money($saleDetail['total'])) ?></th></tr></tfoot></table></div>
    <div class="button-row"><a class="button secondary" href="<?= e(url('src/ventas.php')) ?>">Cerrar detalle</a><?php if ($saleDetail['estado'] === 'confirmada'): ?><form method="post" data-confirm="¿Anular la venta y devolver el stock?"><?= csrf_field() ?><input type="hidden" name="action" value="cancel"><input type="hidden" name="id" value="<?= e($saleDetail['id']) ?>"><button class="button danger" type="submit">Anular venta</button></form><?php endif; ?></div>
</section>
<?php elseif ($viewId > 0): ?><div class="alert error">La venta solicitada no existe.</div><?php endif; ?>

<section class="panel table-panel">
    <div class="panel-toolbar"><h2>Historial</h2><?php if (Auth::can('nueva_venta')): ?><a class="button primary" href="<?= e(url('src/nueva-venta.php')) ?>">Nueva venta</a><?php endif; ?></div>
    <form method="get" class="filter-form"><div><label for="desde">Desde</label><input id="desde" name="desde" type="date" value="<?= e($from) ?>"></div><div><label for="hasta">Hasta</label><input id="hasta" name="hasta" type="date" value="<?= e($to) ?>"></div><button class="button secondary" type="submit">Filtrar</button></form>
    <div class="table-scroll"><table><thead><tr><th>Número</th><th>Fecha</th><th>Cliente</th><th>Vendedor</th><th>Total</th><th>Estado</th><th></th></tr></thead><tbody>
        <?php foreach ($sales as $sale): ?><tr><td>#<?= e($sale['id']) ?></td><td><?= e(date('d/m/Y H:i', strtotime($sale['fecha']))) ?></td><td><?= e($sale['cliente']) ?></td><td><?= e($sale['vendedor']) ?></td><td><?= e(money($sale['total'])) ?></td><td><span class="badge <?= $sale['estado'] === 'confirmada' ? 'active' : 'inactive' ?>"><?= e(ucfirst($sale['estado'])) ?></span></td><td><a href="<?= e(url('src/ventas.php?view=' . $sale['id'] . '&desde=' . urlencode($from) . '&hasta=' . urlencode($to))) ?>">Ver</a></td></tr><?php endforeach; ?>
        <?php if ($sales === []): ?><tr><td colspan="7" class="empty-state">No hay ventas en el período.</td></tr><?php endif; ?>
    </tbody></table></div>
</section>
<?php render_footer(); ?>
