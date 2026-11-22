<?php
$iconos = ['exito' => 'circle-check', 'error' => 'circle-alert', 'info' => 'info', 'alerta' => 'triangle-alert'];
?>
<?php if (!empty($flash)): ?>
    <div class="flash-contenedor">
        <?php foreach ($flash as $f): ?>
            <div class="alerta alerta--<?= e($f['tipo']) ?>" data-autocerrar>
                <i data-lucide="<?= e($iconos[$f['tipo']] ?? 'info') ?>"></i>
                <span><?= e($f['mensaje']) ?></span>
                <button class="alerta-cerrar" data-cerrar-alerta aria-label="Cerrar"><i data-lucide="x"></i></button>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
