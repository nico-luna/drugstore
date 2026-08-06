<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Error</title><link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>"></head>
<body class="error-page"><main><p class="error-code">500</p><h1>No pudimos completar la operación</h1><p><?= e($publicMessage ?? 'Ocurrió un error inesperado.') ?></p><a class="button primary" href="<?= e(url('index.php')) ?>">Volver</a></main></body></html>
