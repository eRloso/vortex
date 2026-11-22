<?php /** Barra de búsqueda + botón "nuevo" para los listados CRUD. */ ?>
<div class="barra-herramientas">
    <form class="buscador" method="get">
        <input type="hidden" name="r" value="<?= e($ruta) ?>">
        <i data-lucide="search"></i>
        <input type="search" name="q" value="<?= e($q ?? '') ?>" placeholder="<?= e($placeholder ?? 'Buscar...') ?>">
        <?php if (!empty($q)): ?>
            <a href="<?= url($ruta) ?>" class="buscador-limpiar" title="Limpiar"><i data-lucide="x"></i></a>
        <?php endif; ?>
    </form>
    <a href="<?= url("$ruta/nuevo") ?>" class="btn btn--primario">
        <i data-lucide="plus"></i><?= e($textoNuevo) ?>
    </a>
</div>
