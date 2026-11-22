<?php
$valor = fn (string $campo, $defecto = '') => $registro[$campo] ?? $defecto;
$error = fn (string $campo) => $errores[$campo] ?? null;
$sugeridos = ['#7C5CFF', '#22D3EE', '#22C55E', '#F59E0B', '#F43F5E', '#EC4899', '#3B82F6', '#14B8A6'];
?>
<form method="post" action="<?= url('categorias/guardar') ?>" class="tarjeta formulario formulario--angosto" novalidate>
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="id" value="<?= e($valor('id')) ?>">

    <div class="formulario-seccion">
        <h2><i data-lucide="tags"></i>Datos de la categoría</h2>
        <div class="rejilla-campos">
            <?= $this->parcial('partials/campo', ['nombre' => 'nombre', 'etiqueta' => 'Nombre', 'valor' => $valor('nombre'), 'error' => $error('nombre'), 'icono' => 'type', 'ancho' => 'completo', 'atributos' => 'required maxlength="80"']) ?>

            <div class="campo campo--completo <?= $error('color') ? 'campo--error' : '' ?>">
                <span class="campo-etiqueta">Color identificador</span>
                <div class="selector-color">
                    <input type="color" name="color" value="<?= e($valor('color', '#7C5CFF')) ?>" data-color-entrada>
                    <div class="paleta">
                        <?php foreach ($sugeridos as $color): ?>
                            <button type="button" class="paleta-muestra" style="--c: <?= $color ?>" data-color="<?= $color ?>" aria-label="<?= $color ?>"></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php if ($error('color')): ?><small class="campo-mensaje campo-mensaje--error"><?= e($error('color')) ?></small><?php endif; ?>
            </div>
        </div>
    </div>

    <?= $this->parcial('partials/form-acciones', ['ruta' => 'categorias', 'editando' => $editando]) ?>
</form>
