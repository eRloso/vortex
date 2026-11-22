<?php
/** @var string $contenido */
$menu = [
    'Principal' => [
        ['ruta' => 'dashboard',    'icono' => 'layout-dashboard', 'texto' => 'Dashboard'],
        ['ruta' => 'ventas/nueva', 'icono' => 'scan-barcode',     'texto' => 'Punto de venta'],
        ['ruta' => 'ventas',       'icono' => 'receipt-text',     'texto' => 'Historial de ventas', 'exacta' => true],
    ],
    'Inventario' => [
        ['ruta' => 'productos',  'icono' => 'package',  'texto' => 'Productos'],
        ['ruta' => 'categorias', 'icono' => 'tags',     'texto' => 'Categorías'],
    ],
    'Relaciones' => [
        ['ruta' => 'clientes', 'icono' => 'users-round', 'texto' => 'Clientes'],
    ],
];
$rutaActual = $_GET['r'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="es" data-tema="oscuro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo ?? 'Panel') ?> · Vortex</title>
    <link rel="icon" href="<?= asset('img/logo.svg') ?>" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/vortex.css') ?>">
    <script>try { document.documentElement.dataset.tema = localStorage.getItem('vortex-tema') || 'oscuro'; } catch (e) {}</script>
    <script src="https://unpkg.com/lucide@0.460.0/dist/umd/lucide.min.js" defer></script>
</head>
<body>
<div class="app">
    <aside class="sidebar" id="sidebar">
        <a class="marca" href="<?= url('dashboard') ?>">
            <img src="<?= asset('img/logo.svg') ?>" alt="" class="marca-logo">
            <span class="marca-texto">Vortex<small>Sistema de ventas</small></span>
        </a>

        <nav class="menu">
            <?php foreach ($menu as $seccion => $items): ?>
                <p class="menu-seccion"><?= e($seccion) ?></p>
                <?php foreach ($items as $item):
                    $activo = !empty($item['exacta']) ? ($rutaActual === $item['ruta'] || $rutaActual === 'ventas/detalle') : ruta_activa($item['ruta']);
                ?>
                    <a href="<?= url($item['ruta']) ?>" class="menu-item <?= $activo ? 'activo' : '' ?>">
                        <i data-lucide="<?= e($item['icono']) ?>"></i>
                        <span><?= e($item['texto']) ?></span>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </nav>

        <a href="<?= url('ventas/nueva') ?>" class="sidebar-cta">
            <i data-lucide="zap"></i>
            <span>Venta rápida</span>
            <kbd>F2</kbd>
        </a>
    </aside>

    <div class="sidebar-fondo" data-cerrar-menu></div>

    <main class="principal">
        <header class="topbar">
            <button class="btn-icono solo-movil" data-abrir-menu aria-label="Abrir menú"><i data-lucide="menu"></i></button>
            <div class="topbar-titulo">
                <h1><?= e($titulo ?? '') ?></h1>
                <?php if (!empty($subtitulo)): ?><p><?= e($subtitulo) ?></p><?php endif; ?>
            </div>
            <div class="topbar-acciones">
                <span class="chip oculto-movil"><i data-lucide="calendar-days"></i><?= date('d/m/Y') ?></span>
                <button class="btn-icono" data-cambiar-tema aria-label="Cambiar tema">
                    <i data-lucide="sun" class="icono-claro"></i>
                    <i data-lucide="moon" class="icono-oscuro"></i>
                </button>
                <?php if ($usuario): ?>
                    <div class="perfil">
                        <span class="avatar"><?= e(iniciales($usuario['nombre'])) ?></span>
                        <span class="perfil-datos oculto-movil">
                            <strong><?= e($usuario['nombre']) ?></strong>
                            <small><?= e(ucfirst($usuario['rol'])) ?></small>
                        </span>
                        <a href="<?= url('logout') ?>" class="btn-icono" title="Cerrar sesión"><i data-lucide="log-out"></i></a>
                    </div>
                <?php endif; ?>
            </div>
        </header>

        <?= $this->parcial('partials/flash', ['flash' => $flash ?? []]) ?>

        <div class="contenido">
            <?= $contenido ?>
        </div>
    </main>
</div>

<div class="toasts" id="toasts" aria-live="polite"></div>

<script src="<?= asset('js/app.js') ?>"></script>
<?php foreach ($scripts ?? [] as $script): ?>
    <script src="<?= e(asset($script)) ?>"></script>
<?php endforeach; ?>
</body>
</html>
