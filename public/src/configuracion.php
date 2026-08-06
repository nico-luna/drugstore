<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';
Auth::requirePermission('configuracion');
$db = Database::connection();
$errors = [];
$config = $db->query('SELECT * FROM configuracion WHERE id = 1')->fetch();

if (is_post()) {
    verify_csrf();
    $config = [
        'nombre' => post_string('nombre'),
        'telefono' => post_string('telefono'),
        'email' => strtolower(post_string('email')),
        'direccion' => post_string('direccion'),
    ];
    if ($config['nombre'] === '' || mb_strlen($config['nombre']) > 100) {
        $errors[] = 'El nombre es obligatorio y admite hasta 100 caracteres.';
    }
    if (mb_strlen($config['telefono']) > 30) {
        $errors[] = 'El teléfono admite hasta 30 caracteres.';
    }
    if ($config['email'] !== '' && (!filter_var($config['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($config['email']) > 190)) {
        $errors[] = 'Ingresá un correo válido.';
    }
    if (mb_strlen($config['direccion']) > 255) {
        $errors[] = 'La dirección admite hasta 255 caracteres.';
    }
    if ($errors === []) {
        $statement = $db->prepare('UPDATE configuracion SET nombre = ?, telefono = ?, email = ?, direccion = ? WHERE id = 1');
        $statement->execute([$config['nombre'], $config['telefono'], $config['email'], $config['direccion']]);
        flash('success', 'Configuración actualizada.');
        redirect('src/configuracion.php');
    }
}

render_header('Configuración');
render_validation_errors($errors);
?>
<section class="panel narrow-panel">
    <h2>Datos del negocio</h2>
    <form method="post" class="stacked-form">
        <?= csrf_field() ?>
        <label for="nombre">Nombre comercial</label><input id="nombre" name="nombre" maxlength="100" required value="<?= e($config['nombre'] ?? '') ?>">
        <label for="telefono">Teléfono</label><input id="telefono" name="telefono" maxlength="30" value="<?= e($config['telefono'] ?? '') ?>">
        <label for="email">Correo</label><input id="email" name="email" type="email" maxlength="190" value="<?= e($config['email'] ?? '') ?>">
        <label for="direccion">Dirección</label><input id="direccion" name="direccion" maxlength="255" value="<?= e($config['direccion'] ?? '') ?>">
        <button class="button primary" type="submit">Guardar cambios</button>
    </form>
</section>
<?php render_footer(); ?>
