<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';
Auth::requirePermission('nueva_venta');
$db = Database::connection();
$errors = [];
$selectedClient = (int) ($_POST['cliente_id'] ?? 1);

if (is_post()) {
    verify_csrf();
    $productIds = (array) ($_POST['producto_id'] ?? []);
    $quantities = (array) ($_POST['cantidad'] ?? []);
    $lines = [];
    foreach ($productIds as $index => $productId) {
        $lines[] = ['producto_id' => $productId, 'cantidad' => $quantities[$index] ?? null];
    }
    try {
        $saleId = (new SaleService($db))->create($selectedClient, (int) Auth::user()['idusuario'], $lines);
        flash('success', 'Venta #' . $saleId . ' registrada correctamente.');
        redirect('src/ventas.php?view=' . $saleId);
    } catch (InvalidArgumentException|RuntimeException $exception) {
        $errors[] = $exception->getMessage();
    }
}

$clients = $db->query('SELECT idcliente, nombre FROM cliente WHERE estado = 1 ORDER BY nombre')->fetchAll();
$products = $db->query(
    'SELECT codproducto, codigo, descripcion, precio, existencia, controla_stock
     FROM producto
     WHERE estado = 1 AND (controla_stock = 0 OR existencia > 0)
     ORDER BY descripcion'
)->fetchAll();

render_header('Nueva venta');
render_validation_errors($errors);
?>
<section class="panel sale-panel">
    <?php if ($products === []): ?>
        <div class="empty-state"><h2>No hay productos disponibles</h2><p>Creá o activá productos con stock antes de registrar una venta.</p><?php if (Auth::can('productos')): ?><a class="button primary" href="<?= e(url('src/productos.php')) ?>">Ir a productos</a><?php endif; ?></div>
    <?php else: ?>
    <form method="post" id="sale-form" class="stacked-form">
        <?= csrf_field() ?>
        <div class="sale-client"><label for="cliente_id">Cliente</label><select id="cliente_id" name="cliente_id" required><?php foreach ($clients as $client): ?><option value="<?= e($client['idcliente']) ?>" <?= (int) $client['idcliente'] === $selectedClient ? 'selected' : '' ?>><?= e($client['nombre']) ?></option><?php endforeach; ?></select></div>
        <div class="sale-lines-heading"><h2>Productos</h2><button class="button secondary" type="button" data-add-sale-line>Agregar producto</button></div>
        <div id="sale-lines" class="sale-lines">
            <div class="sale-line" data-sale-line>
                <div><label>Producto<select name="producto_id[]" required data-product-select><option value="">Seleccionar…</option><?php foreach ($products as $product): ?><option value="<?= e($product['codproducto']) ?>" data-price="<?= e($product['precio']) ?>" data-stock="<?= e($product['existencia']) ?>" data-controls-stock="<?= e($product['controla_stock']) ?>"><?= e($product['codigo'] . ' · ' . $product['descripcion']) ?> — <?= e(money($product['precio'])) ?></option><?php endforeach; ?></select></label></div>
                <div><label>Cantidad<input name="cantidad[]" type="number" min="1" max="100000" step="1" value="1" required data-quantity></label></div>
                <div class="line-subtotal"><span>Subtotal</span><strong data-line-total>$ 0,00</strong></div>
                <button class="icon-button" type="button" data-remove-sale-line aria-label="Quitar producto">×</button>
            </div>
        </div>
        <template id="sale-line-template">
            <div class="sale-line" data-sale-line>
                <div><label>Producto<select name="producto_id[]" required data-product-select><option value="">Seleccionar…</option><?php foreach ($products as $product): ?><option value="<?= e($product['codproducto']) ?>" data-price="<?= e($product['precio']) ?>" data-stock="<?= e($product['existencia']) ?>" data-controls-stock="<?= e($product['controla_stock']) ?>"><?= e($product['codigo'] . ' · ' . $product['descripcion']) ?> — <?= e(money($product['precio'])) ?></option><?php endforeach; ?></select></label></div>
                <div><label>Cantidad<input name="cantidad[]" type="number" min="1" max="100000" step="1" value="1" required data-quantity></label></div>
                <div class="line-subtotal"><span>Subtotal</span><strong data-line-total>$ 0,00</strong></div>
                <button class="icon-button" type="button" data-remove-sale-line aria-label="Quitar producto">×</button>
            </div>
        </template>
        <div class="sale-summary"><span>Total estimado</span><strong data-sale-total>$ 0,00</strong></div>
        <p class="field-help">El precio y el stock se validan nuevamente en el servidor al confirmar.</p>
        <button class="button primary large" type="submit">Confirmar venta</button>
    </form>
    <?php endif; ?>
</section>
<?php render_footer(); ?>
