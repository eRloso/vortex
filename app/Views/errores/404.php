<!DOCTYPE html>
<html lang="es" data-tema="oscuro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página no encontrada · Vortex</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/vortex.css') ?>">
</head>
<body class="pagina-error">
<main class="error">
    <img src="<?= asset('img/logo.svg') ?>" alt="" class="login-logo">
    <h1>404</h1>
    <p>La página que buscas fue absorbida por el vórtice.</p>
    <a href="<?= url('dashboard') ?>" class="btn btn--primario">Volver al inicio</a>
</main>
</body>
</html>
