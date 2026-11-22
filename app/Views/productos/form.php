<?php
$valor = fn (string $campo, $defecto = '') => $registro[$campo] ?? $defecto;
$error = fn (string $campo) => $errores[$campo] ?? null;
?>
<form method="post" action="<?= url('productos/guardar') ?>" class="tarjeta formulario" novalidate>
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="id" value="<?= e($valor('id')) ?>">

    <div class="formulario-seccion">
        <h2><i data-lucide="package"></i>Información del producto</h2>
        <div class="rejilla-campos">
            <?= $this->parcial('partials/campo', ['nombre' => 'codigo', 'etiqueta' => 'Código', 'valor' => $valor('codigo'), 'error' => $error('codigo'), 'icono' => 'barcode', 'atributos' => 'required maxlength="30" placeholder="VTX-XXX-000"']) ?>
            <?= $this->parcial('partials/campo', ['nombre' => 'nombre', 'etiqueta' => 'Nombre', 'valor' => $valor('nombre'), 'error' => $error('nombre'), 'icono' => 'type', 'atributos' => 'required maxlength="120"']) ?>

            <label class="campo">
                <span class="campo-etiqueta">Categoría</span>
                <span class="campo-control con-icono">
                    <i data-lucide="tags"></i>
                    <select name="categoria_id">
                        <option value="">Sin categoría</option>
                        <?php foreach ($categorias as $c): ?>
                            <option value="<?= (int) $c['id'] ?>" <?= (string) $valor('categoria_id') === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </span>
            </label>

            <?= $this->parcial('partials/campo', ['nombre' => 'precio', 'etiqueta' => 'Precio de venta (COP)', 'tipo' => 'number', 'valor' => $valor('precio'), 'error' => $error('precio'), 'icono' => 'dollar-sign', 'atributos' => 'required min="1" step="0.01"']) ?>
            <?= $this->parcial('partials/campo', ['nombre' => 'descripcion', 'etiqueta' => 'Descripción', 'tipo' => 'textarea', 'valor' => $valor('descripcion'), 'ancho' => 'completo', 'atributos' => 'placeholder="Opcional"']) ?>
        </div>
    </div>

    <div class="formulario-seccion">
        <h2><i data-lucide="warehouse"></i>Inventario</h2>
        <div class="rejilla-campos">
            <?= $this->parcial('partials/campo', ['nombre' => 'stock', 'etiqueta' => 'Stock actual', 'tipo' => 'number', 'valor' => $valor('stock', 0), 'error' => $error('stock'), 'icono' => 'boxes', 'atributos' => 'min="0" step="1"']) ?>
            <?= $this->parcial('partials/campo', ['nombre' => 'stock_minimo', 'etiqueta' => 'Stock mínimo', 'tipo' => 'number', 'valor' => $valor('stock_minimo', 5), 'error' => $error('stock_minimo'), 'icono' => 'bell-ring', 'ayuda' => 'Recibirás una alerta cuando el stock llegue a este número.', 'atributos' => 'min="0" step="1"']) ?>
        </div>
    </div>

    <?= $this->parcial('partials/form-acciones', ['ruta' => 'productos', 'editando' => $editando]) ?>
</form>
