<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

if (Auth::check()) {
    redirect('src/index.php');
}

$error = flash('error');
$username = '';
if (is_post()) {
    verify_csrf();
    $username = post_string('usuario');
    $password = (string) ($_POST['clave'] ?? '');
    if ($username === '' || $password === '') {
        $error = 'Ingresá tu usuario y contraseña.';
    } else {
        try {
            if (Auth::attempt($username, $password)) {
                redirect('src/index.php');
            }
            $error = 'Usuario o contraseña incorrectos.';
        } catch (RuntimeException $exception) {
            $error = $exception->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Iniciar sesión · Drugstore</title>
    <link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>">
</head>
<body class="login-page">
<main class="login-card">
    <div class="login-brand">Drugstore</div>
    <h1>Iniciar sesión</h1>
    <p class="muted">Ingresá para administrar el negocio.</p>
    <?php if ($error): ?><div class="alert error" role="alert"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="stacked-form">
        <?= csrf_field() ?>
        <label for="usuario">Usuario</label>
        <input id="usuario" name="usuario" value="<?= e($username) ?>" autocomplete="username" required autofocus maxlength="50">
        <label for="clave">Contraseña</label>
        <input id="clave" name="clave" type="password" autocomplete="current-password" required>
        <button class="button primary full" type="submit">Ingresar</button>
    </form>
</main>
</body>
</html>
