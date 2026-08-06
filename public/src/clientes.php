<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';
Auth::requirePermission('clientes');
$db = Database::connection();
$errors = [];
$editing = null;

if (is_post()) {
    verify_csrf();
    $action = post_string('action');
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'save') {
        $nombre = post_string('nombre');
        $telefono = post_string('telefono');
        $direccion = post_string('direccion');
        if ($nombre === '' || mb_strlen($nombre) > 100) {
            $errors[] = 'El nombre es obligatorio y admite hasta 100 caracteres.';
        }
        if (mb_strlen($telefono) > 30) {
            $errors[] = 'El teléfono admite hasta 30 caracteres.';
        }
        if (mb_strlen($direccion) > 200) {
            $errors[] = 'La dirección admite hasta 200 caracteres.';
        }
        if ($errors === []) {
            if ($id > 0) {
                $statement = $db->prepare('UPDATE cliente SET nombre = ?, telefono = ?, direccion = ? WHERE idcliente = ?');
                $statement->execute([$nombre, $telefono, $direccion, $id]);
                flash('success', 'Cliente actualizado.');
            } else {
                $statement = $db->prepare('INSERT INTO cliente (nombre, telefono, direccion, usuario_id) VALUES (?, ?, ?, ?)');
                $statement->execute([$nombre, $telefono, $direccion, Auth::user()['idusuario']]);
                flash('success', 'Cliente creado.');
            }
            redirect('src/clientes.php');
        }
        $editing = ['idcliente' => $id, 'nombre' => $nombre, 'telefono' => $telefono, 'direccion' => $direccion];
    } elseif ($action === 'toggle' && $id > 0) {
        if ($id === 1) {
            flash('error', 'El cliente Público en general no puede desactivarse.');
        } else {
            $statement = $db->prepare('UPDATE cliente SET estado = NOT estado WHERE idcliente = ?');
            $statement->execute([$id]);
            flash('success', 'Estado del cliente actualizado.');
        }
        redirect('src/clientes.php');
    }
}

$editId = request_int('edit');
if ($editing === null && $editId > 0) {
    $statement = $db->prepare('SELECT * FROM cliente WHERE idcliente = ?');
    $statement->execute([$editId]);
    $editing = $statement->fetch() ?: null;
}

$search = trim((string) ($_GET['q'] ?? ''));
if ($search !== '') {
    $statement = $db->prepare('SELECT * FROM cliente WHERE nombre LIKE ? OR telefono LIKE ? ORDER BY estado DESC, nombre LIMIT 200');
    $term = '%' . $search . '%';
    $statement->execute([$term, $term]);
    $clients = $statement->fetchAll();
} else {
    $clients = $db->query('SELECT * FROM cliente ORDER BY estado DESC, nombre LIMIT 200')->fetchAll();
}

render_header('Clientes');
render_validation_errors($errors);
?>
<div class="content-grid">
    <section class="panel form-panel">
        <h2><?= $editing ? 'Editar cliente' : 'Nuevo cliente' ?></h2>
        <form method="post" class="stacked-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= e($editing['idcliente'] ?? 0) ?>">
            <label for="nombre">Nombre</label>
            <input id="nombre" name="nombre" maxlength="100" required value="<?= e($editing['nombre'] ?? '') ?>">
            <label for="telefono">Teléfono</label>
            <input id="telefono" name="telefono" maxlength="30" value="<?= e($editing['telefono'] ?? '') ?>">
            <label for="direccion">Dirección</label>
            <input id="direccion" name="direccion" maxlength="200" value="<?= e($editing['direccion'] ?? '') ?>">
            <div class="button-row">
                <button class="button primary" type="submit">Guardar</button>
                <?php if ($editing): ?><a class="button secondary" href="<?= e(url('src/clientes.php')) ?>">Cancelar</a><?php endif; ?>
            </div>
        </form>
    </section>
    <section class="panel table-panel">
        <div class="panel-toolbar">
            <h2>Listado</h2>
            <form method="get" class="search-form">
                <label class="sr-only" for="q">Buscar cliente</label>
                <input id="q" name="q" value="<?= e($search) ?>" placeholder="Nombre o teléfono">
                <button class="button secondary" type="submit">Buscar</button>
            </form>
        </div>
        <div class="table-scroll">
            <table>
                <thead><tr><th>Nombre</th><th>Teléfono</th><th>Dirección</th><th>Estado</th><th>Acciones</th></tr></thead>
                <tbody>
                <?php foreach ($clients as $client): ?>
                    <tr>
                        <td><?= e($client['nombre']) ?></td><td><?= e($client['telefono']) ?></td><td><?= e($client['direccion']) ?></td>
                        <td><?= status_badge((bool) $client['estado']) ?></td>
                        <td class="actions">
                            <a href="<?= e(url('src/clientes.php?edit=' . $client['idcliente'])) ?>">Editar</a>
                            <?php if ((int) $client['idcliente'] !== 1): ?>
                                <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= e($client['idcliente']) ?>"><button class="link-button" type="submit"><?= $client['estado'] ? 'Desactivar' : 'Activar' ?></button></form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($clients === []): ?><tr><td colspan="5" class="empty-state">No hay clientes para mostrar.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php render_footer(); ?>
