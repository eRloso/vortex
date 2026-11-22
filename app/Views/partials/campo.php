<?php
/**
 * Campo de formulario reutilizable.
 * Parámetros: nombre, etiqueta, tipo, valor, error, icono, ayuda, atributos, ancho ('completo')
 */
$tipo      = $tipo ?? 'text';
$valor     = $valor ?? '';
$error     = $error ?? null;
$atributos = $atributos ?? '';
$clases    = trim('campo ' . ($error ? 'campo--error ' : '') . (($ancho ?? '') === 'completo' ? 'campo--completo' : ''));
?>
<label class="<?= $clases ?>">
    <span class="campo-etiqueta"><?= e($etiqueta) ?></span>
    <span class="campo-control <?= !empty($icono) ? 'con-icono' : '' ?>">
        <?php if (!empty($icono)): ?><i data-lucide="<?= e($icono) ?>"></i><?php endif; ?>
        <?php if ($tipo === 'textarea'): ?>
            <textarea name="<?= e($nombre) ?>" rows="3" <?= $atributos ?>><?= e($valor) ?></textarea>
        <?php else: ?>
            <input type="<?= e($tipo) ?>" name="<?= e($nombre) ?>" value="<?= e($valor) ?>" <?= $atributos ?>>
        <?php endif; ?>
    </span>
    <?php if ($error): ?>
        <small class="campo-mensaje campo-mensaje--error"><?= e($error) ?></small>
    <?php elseif (!empty($ayuda)): ?>
        <small class="campo-mensaje"><?= e($ayuda) ?></small>
    <?php endif; ?>
</label>
