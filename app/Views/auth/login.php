<!DOCTYPE html>
<html lang="es" data-tema="oscuro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión · Vortex</title>
    <link rel="icon" href="<?= asset('img/logo.svg') ?>" type="image/svg+xml">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/vortex.css') ?>">
    <script>try { document.documentElement.dataset.tema = localStorage.getItem('vortex-tema') || 'oscuro'; } catch (e) {}</script>
    <script src="https://unpkg.com/lucide@0.460.0/dist/umd/lucide.min.js" defer></script>
</head>
<body class="pagina-login">
<div class="login-fondo" aria-hidden="true">
    <span class="orbita orbita--1"></span>
    <span class="orbita orbita--2"></span>
    <span class="orbita orbita--3"></span>
</div>

<main class="login">
    <section class="login-presentacion">
        <img src="<?= asset('img/logo.svg') ?>" alt="Vortex" class="login-logo">
        <h1>Vortex</h1>
        <p>Vende más rápido, controla tu inventario y conoce tu negocio en tiempo real.</p>
        <ul>
            <li><i data-lucide="scan-barcode"></i>Punto de venta ágil</li>
            <li><i data-lucide="chart-line"></i>Reportes y estadísticas</li>
            <li><i data-lucide="globe"></i>Conversión a USD con API en vivo</li>
        </ul>
    </section>

    <form class="tarjeta login-formulario" method="post" action="<?= url('login') ?>">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <h2>Iniciar sesión</h2>
        <p class="texto-suave">Ingresa tus credenciales para continuar</p>

        <?= $this->parcial('partials/flash', ['flash' => $flash ?? []]) ?>
        <?php if (!empty($error)): ?>
            <div class="alerta alerta--error"><i data-lucide="circle-alert"></i><span><?= e($error) ?></span></div>
        <?php endif; ?>

        <?= $this->parcial('partials/campo', ['nombre' => 'email', 'etiqueta' => 'Correo electrónico', 'tipo' => 'email', 'valor' => $email, 'icono' => 'mail', 'atributos' => 'required autofocus placeholder="admin@vortex.com"']) ?>
        <?= $this->parcial('partials/campo', ['nombre' => 'password', 'etiqueta' => 'Contraseña', 'tipo' => 'password', 'icono' => 'lock', 'atributos' => 'required placeholder="••••••••"']) ?>

        <button class="btn btn--primario btn--bloque btn--grande"><i data-lucide="log-in"></i>Entrar</button>
        <p class="login-demo">Demo: <span class="mono">admin@vortex.com</span> / <span class="mono">admin123</span></p>
    </form>
</main>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
