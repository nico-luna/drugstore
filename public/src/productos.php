<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';
Auth::requirePermission('productos');
$db = Database::connection();
$errors = [];
$editing = null;

if (is_post()) {
    verify_csrf();
    $action = post_string('action');
    $id = (int) ($_POST['id'] ?? 0);
    if ($action === 'save') {
        $codigo = post_string('codigo');
        $descripcion = post_string('descripcion');
        $precioRaw = str_replace(',', '.', post_string('precio'));
        $existenciaRaw = post_string('existencia');
        $controlaStock = isset($_POST['controla_stock']) ? 1 : 0;
        $precio = filter_var($precioRaw, FILTER_VALIDATE_FLOAT);
        $existencia = filter_var($existenciaRaw, FILTER_VALIDATE_INT);
        if ($codigo === '' || mb_strlen($codigo) > 50) {
            $errors[] = 'El código es obligatorio y admite hasta 50 caracteres.';
        }
        if ($descripcion === '' || mb_strlen($descripcion) > 200) {
            $errors[] = 'La descripción es obligatoria y admite hasta 200 caracteres.';
        }
        if ($precio === false || $precio < 0 || $precio > 9999999999.99) {
            $errors[] = 'El precio no es válido.';
        }
        if ($existencia === false || $existencia < 0) {
            $errors[] = 'La existencia debe ser un entero mayor o igual a cero.';
        }
        if ($errors === []) {
            try {
                if ($id > 0) {
                    $statement = $db->prepare('UPDATE producto SET codigo = ?, descripcion = ?, precio = ?, existencia = ?, controla_stock = ? WHERE codproducto = ?');
                    $statement->execute([$codigo, $descripcion, number_format((float) $precio, 2, '.', ''), $existencia, $controlaStock, $id]);
                    flash('success', 'Producto actualizado.');
                } else {
                    $statement = $db->prepare('INSERT INTO producto (codigo, descripcion, precio, existencia, controla_stock, usuario_id) VALUES (?, ?, ?, ?, ?, ?)');
                    $statement->execute([$codigo, $descripcion, number_format((float) $precio, 2, '.', ''), $existencia, $controlaStock, Auth::user()['idusuario']]);
                    flash('success', 'Producto creado.');
                }
                redirect('src/productos.php');
            } catch (PDOException $exception) {
                if ($exception->getCode() === '23000') {
                    $errors[] = 'Ya existe un producto con ese código.';
                } else {
                    throw $exception;
                }
            }
        }
        $editing = ['codproducto' => $id, 'codigo' => $codigo, 'descripcion' => $descripcion, 'precio' => $precioRaw, 'existencia' => $existenciaRaw, 'controla_stock' => $controlaStock];
    } elseif ($action === 'toggle' && $id > 0) {
        $db->prepare('UPDATE producto SET estado = NOT estado WHERE codproducto = ?')->execute([$id]);
        flash('success', 'Estado del producto actualizado.');
        redirect('src/productos.php');
    }
}

$editId = request_int('edit');
if ($editing === null && $editId > 0) {
    $statement = $db->prepare('SELECT * FROM producto WHERE codproducto = ?');
    $statement->execute([$editId]);
    $editing = $statement->fetch() ?: null;
}
$search = trim((string) ($_GET['q'] ?? ''));
if ($search !== '') {
    $statement = $db->prepare('SELECT * FROM producto WHERE codigo LIKE ? OR descripcion LIKE ? ORDER BY estado DESC, descripcion LIMIT 200');
    $term = '%' . $search . '%';
    $statement->execute([$term, $term]);
    $products = $statement->fetchAll();
} else {
    $products = $db->query('SELECT * FROM producto ORDER BY estado DESC, descripcion LIMIT 200')->fetchAll();
}

render_header('Productos');
render_validation_errors($errors);
?>
<div class="content-grid">
    <section class="panel form-panel">
        <h2><?= $editing ? 'Editar producto' : 'Nuevo producto' ?></h2>
        <form method="post" class="stacked-form">
            <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= e($editing['codproducto'] ?? 0) ?>">
            <label for="codigo">Código</label><input id="codigo" name="codigo" maxlength="50" required value="<?= e($editing['codigo'] ?? '') ?>">
            <label for="descripcion">Descripción</label><input id="descripcion" name="descripcion" maxlength="200" required value="<?= e($editing['descripcion'] ?? '') ?>">
            <div class="field-row">
                <div><label for="precio">Precio</label><input id="precio" name="precio" type="number" min="0" max="9999999999.99" step="0.01" required value="<?= e($editing['precio'] ?? '') ?>"></div>
                <div><label for="existencia">Existencia</label><input id="existencia" name="existencia" type="number" min="0" step="1" required value="<?= e($editing['existencia'] ?? 0) ?>"></div>
            </div>
            <label class="check-row"><input name="controla_stock" type="checkbox" value="1" <?= !isset($editing) || !empty($editing['controla_stock']) ? 'checked' : '' ?>> Descontar stock en cada venta</label>
            <div class="button-row"><button class="button primary" type="submit">Guardar</button><?php if ($editing): ?><a class="button secondary" href="<?= e(url('src/productos.php')) ?>">Cancelar</a><?php endif; ?></div>
        </form>
    </section>
    <section class="panel table-panel">
        <div class="panel-toolbar"><h2>Listado</h2><form method="get" class="search-form"><label class="sr-only" for="q">Buscar producto</label><input id="q" name="q" value="<?= e($search) ?>" placeholder="Código o descripción"><button class="button secondary" type="submit">Buscar</button></form></div>
        <div class="table-scroll"><table>
            <thead><tr><th>Código</th><th>Descripción</th><th>Precio</th><th>Stock</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($products as $product): ?><tr>
                <td><?= e($product['codigo']) ?></td><td><?= e($product['descripcion']) ?></td><td><?= e(money($product['precio'])) ?></td>
                <td><?= $product['controla_stock'] ? e($product['existencia']) : '<span class="muted">Sin control</span>' ?></td><td><?= status_badge((bool) $product['estado']) ?></td>
                <td class="actions"><a href="<?= e(url('src/productos.php?edit=' . $product['codproducto'])) ?>">Editar</a><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= e($product['codproducto']) ?>"><button class="link-button" type="submit"><?= $product['estado'] ? 'Desactivar' : 'Activar' ?></button></form></td>
            </tr><?php endforeach; ?>
            <?php if ($products === []): ?><tr><td colspan="6" class="empty-state">No hay productos para mostrar.</td></tr><?php endif; ?>
            </tbody>
        </table></div>
    </section>
</div>
<?php render_footer(); ?>
