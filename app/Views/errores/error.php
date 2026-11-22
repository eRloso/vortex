<!DOCTYPE html>
<html lang="es" data-tema="oscuro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error · Vortex</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/vortex.css') ?>">
</head>
<body class="pagina-error">
<main class="error">
    <img src="<?= asset('img/logo.svg') ?>" alt="" class="login-logo">
    <h1>Ups…</h1>
    <p><?= e($mensaje) ?></p>
    <?php if (!empty($detalle)): ?><pre class="error-detalle"><?= e($detalle) ?></pre><?php endif; ?>
    <a href="<?= url('dashboard') ?>" class="btn btn--primario">Reintentar</a>
</main>
</body>
</html>
