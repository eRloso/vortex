<div class="vacio">
    <span class="vacio-icono"><i data-lucide="<?= e($icono ?? 'inbox') ?>"></i></span>
    <h3><?= e($titulo ?? 'Sin resultados') ?></h3>
    <p><?= e($texto ?? 'No hay registros para mostrar.') ?></p>
    <?php if (!empty($accion)): ?>
        <a href="<?= e($accion['url']) ?>" class="btn btn--primario"><i data-lucide="plus"></i><?= e($accion['texto']) ?></a>
    <?php endif; ?>
</div>
