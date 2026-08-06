<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';
Auth::requirePermission('usuarios');
$db = Database::connection();
$errors = [];
$editing = null;
$current = Auth::user();
$permissions = $db->query('SELECT id, nombre, etiqueta FROM permisos ORDER BY id')->fetchAll();

if (is_post()) {
    verify_csrf();
    $action = post_string('action');
    $id = (int) ($_POST['id'] ?? 0);
    if ($action === 'save') {
        $nombre = post_string('nombre');
        $correo = strtolower(post_string('correo'));
        $username = post_string('usuario');
        $password = (string) ($_POST['clave'] ?? '');
        $isAdmin = (bool) $current['es_admin'] && isset($_POST['es_admin']) ? 1 : 0;
        $selectedPermissions = array_values(array_unique(array_filter(array_map('intval', (array) ($_POST['permisos'] ?? [])))));

        if ($nombre === '' || mb_strlen($nombre) > 100) {
            $errors[] = 'El nombre es obligatorio y admite hasta 100 caracteres.';
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || mb_strlen($correo) > 190) {
            $errors[] = 'Ingresá un correo válido.';
        }
        if (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $username)) {
            $errors[] = 'El usuario debe tener entre 3 y 50 caracteres y solo puede incluir letras, números, punto, guion y guion bajo.';
        }
        if ($id === 0 && $password === '') {
            $errors[] = 'La contraseña es obligatoria para un usuario nuevo.';
        }
        if ($password !== '' && ($passwordError = validate_password_strength($password))) {
            $errors[] = $passwordError;
        }

        if ($id > 0) {
            $existing = $db->prepare('SELECT es_admin FROM usuario WHERE idusuario = ?');
            $existing->execute([$id]);
            $existingUser = $existing->fetch();
            if (!$existingUser) {
                $errors[] = 'El usuario no existe.';
            } elseif ((bool) $existingUser['es_admin'] && !(bool) $current['es_admin']) {
                $errors[] = 'Solo otro administrador puede modificar esta cuenta.';
            } elseif (!(bool) $current['es_admin']) {
                $isAdmin = 0;
            }
        }

        if ($errors === []) {
            $db->beginTransaction();
            try {
                if ($id > 0) {
                    $sql = 'UPDATE usuario SET nombre = ?, correo = ?, usuario = ?, es_admin = ?';
                    $params = [$nombre, $correo, $username, $isAdmin];
                    if ($password !== '') {
                        $sql .= ', clave = ?';
                        $params[] = password_hash($password, PASSWORD_DEFAULT);
                    }
                    $sql .= ' WHERE idusuario = ?';
                    $params[] = $id;
                    $db->prepare($sql)->execute($params);
                } else {
                    $statement = $db->prepare('INSERT INTO usuario (nombre, correo, usuario, clave, es_admin) VALUES (?, ?, ?, ?, ?)');
                    $statement->execute([$nombre, $correo, $username, password_hash($password, PASSWORD_DEFAULT), $isAdmin]);
                    $id = (int) $db->lastInsertId();
                }
                $db->prepare('DELETE FROM detalle_permisos WHERE id_usuario = ?')->execute([$id]);
                if (!$isAdmin && $selectedPermissions !== []) {
                    $validIds = array_map(static fn(array $permission): int => (int) $permission['id'], $permissions);
                    $insert = $db->prepare('INSERT INTO detalle_permisos (id_permiso, id_usuario) VALUES (?, ?)');
                    foreach ($selectedPermissions as $permissionId) {
                        if (in_array($permissionId, $validIds, true)) {
                            $insert->execute([$permissionId, $id]);
                        }
                    }
                }
                $db->commit();
                flash('success', 'Usuario guardado.');
                redirect('src/usuarios.php');
            } catch (PDOException $exception) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                if ($exception->getCode() === '23000') {
                    $errors[] = 'El usuario o el correo ya están registrados.';
                } else {
                    throw $exception;
                }
            }
        }
        $editing = ['idusuario' => $id, 'nombre' => $nombre, 'correo' => $correo, 'usuario' => $username, 'es_admin' => $isAdmin, 'permission_ids' => $selectedPermissions];
    } elseif ($action === 'toggle' && $id > 0) {
        if ($id === (int) $current['idusuario']) {
            flash('error', 'No podés desactivar tu propia cuenta.');
            redirect('src/usuarios.php');
        }
        $target = $db->prepare('SELECT es_admin, estado FROM usuario WHERE idusuario = ?');
        $target->execute([$id]);
        $targetUser = $target->fetch();
        if (!$targetUser) {
            flash('error', 'El usuario no existe.');
        } elseif ((bool) $targetUser['es_admin'] && !(bool) $current['es_admin']) {
            flash('error', 'Solo un administrador puede modificar otra cuenta administradora.');
        } elseif ((bool) $targetUser['es_admin'] && (bool) $targetUser['estado'] && (int) $db->query('SELECT COUNT(*) FROM usuario WHERE es_admin = 1 AND estado = 1')->fetchColumn() <= 1) {
            flash('error', 'Debe quedar al menos un administrador activo.');
        } else {
            $db->prepare('UPDATE usuario SET estado = NOT estado WHERE idusuario = ?')->execute([$id]);
            flash('success', 'Estado del usuario actualizado.');
        }
        redirect('src/usuarios.php');
    }
}

$editId = request_int('edit');
if ($editing === null && $editId > 0) {
    $statement = $db->prepare(
        'SELECT u.*, GROUP_CONCAT(dp.id_permiso) AS permission_ids
         FROM usuario u LEFT JOIN detalle_permisos dp ON dp.id_usuario = u.idusuario
         WHERE u.idusuario = ? GROUP BY u.idusuario'
    );
    $statement->execute([$editId]);
    $editing = $statement->fetch() ?: null;
    if ($editing && (bool) $editing['es_admin'] && !(bool) $current['es_admin']) {
        flash('error', 'Solo otro administrador puede modificar esa cuenta.');
        redirect('src/usuarios.php');
    }
    if ($editing) {
        $editing['permission_ids'] = $editing['permission_ids'] ? array_map('intval', explode(',', $editing['permission_ids'])) : [];
    }
}
$users = $db->query('SELECT idusuario, nombre, correo, usuario, es_admin, estado FROM usuario ORDER BY estado DESC, nombre')->fetchAll();

render_header('Usuarios');
render_validation_errors($errors);
?>
<div class="content-grid">
    <section class="panel form-panel">
        <h2><?= $editing ? 'Editar usuario' : 'Nuevo usuario' ?></h2>
        <form method="post" class="stacked-form">
            <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= e($editing['idusuario'] ?? 0) ?>">
            <label for="nombre">Nombre</label><input id="nombre" name="nombre" maxlength="100" required value="<?= e($editing['nombre'] ?? '') ?>">
            <label for="correo">Correo</label><input id="correo" name="correo" type="email" maxlength="190" required value="<?= e($editing['correo'] ?? '') ?>">
            <label for="usuario">Usuario</label><input id="usuario" name="usuario" maxlength="50" required autocomplete="off" value="<?= e($editing['usuario'] ?? '') ?>">
            <label for="clave">Contraseña <?= $editing ? '(dejar vacía para conservar)' : '' ?></label><input id="clave" name="clave" type="password" autocomplete="new-password" <?= $editing ? '' : 'required' ?>>
            <p class="field-help">Mínimo 12 caracteres, con mayúsculas, minúsculas, números y símbolos.</p>
            <?php if ((bool) $current['es_admin']): ?><label class="check-row"><input name="es_admin" type="checkbox" value="1" <?= !empty($editing['es_admin']) ? 'checked' : '' ?>> Administrador con acceso total</label><?php endif; ?>
            <fieldset class="permission-list" <?= !empty($editing['es_admin']) ? 'disabled' : '' ?>><legend>Permisos específicos</legend>
                <?php foreach ($permissions as $permission): ?><label class="check-row"><input type="checkbox" name="permisos[]" value="<?= e($permission['id']) ?>" <?= in_array((int) $permission['id'], $editing['permission_ids'] ?? [], true) ? 'checked' : '' ?>> <?= e($permission['etiqueta']) ?></label><?php endforeach; ?>
            </fieldset>
            <div class="button-row"><button class="button primary" type="submit">Guardar</button><?php if ($editing): ?><a class="button secondary" href="<?= e(url('src/usuarios.php')) ?>">Cancelar</a><?php endif; ?></div>
        </form>
    </section>
    <section class="panel table-panel">
        <h2>Listado</h2><div class="table-scroll"><table>
            <thead><tr><th>Nombre</th><th>Usuario</th><th>Correo</th><th>Acceso</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody><?php foreach ($users as $user): ?><tr>
                <td><?= e($user['nombre']) ?></td><td><?= e($user['usuario']) ?></td><td><?= e($user['correo']) ?></td><td><?= $user['es_admin'] ? '<span class="badge admin">Administrador</span>' : 'Permisos asignados' ?></td><td><?= status_badge((bool) $user['estado']) ?></td>
                <td class="actions"><a href="<?= e(url('src/usuarios.php?edit=' . $user['idusuario'])) ?>">Editar</a><?php if ((int) $user['idusuario'] !== (int) $current['idusuario']): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= e($user['idusuario']) ?>"><button class="link-button" type="submit"><?= $user['estado'] ? 'Desactivar' : 'Activar' ?></button></form><?php endif; ?></td>
            </tr><?php endforeach; ?></tbody>
        </table></div>
    </section>
</div>
<?php render_footer(); ?>
