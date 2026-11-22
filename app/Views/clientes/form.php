<?php
$valor = fn (string $campo, $defecto = '') => $registro[$campo] ?? $defecto;
$error = fn (string $campo) => $errores[$campo] ?? null;
?>
<form method="post" action="<?= url('clientes/guardar') ?>" class="tarjeta formulario" novalidate>
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="id" value="<?= e($valor('id')) ?>">

    <div class="formulario-seccion">
        <h2><i data-lucide="id-card"></i>Identificación</h2>
        <div class="rejilla-campos">
            <?= $this->parcial('partials/campo', ['nombre' => 'documento', 'etiqueta' => 'Documento', 'valor' => $valor('documento'), 'error' => $error('documento'), 'icono' => 'hash', 'atributos' => 'required maxlength="20" inputmode="numeric"']) ?>
            <?= $this->parcial('partials/campo', ['nombre' => 'nombre', 'etiqueta' => 'Nombre completo', 'valor' => $valor('nombre'), 'error' => $error('nombre'), 'icono' => 'user', 'atributos' => 'required maxlength="120"']) ?>
        </div>
    </div>

    <div class="formulario-seccion">
        <h2><i data-lucide="contact"></i>Contacto</h2>
        <div class="rejilla-campos">
            <?= $this->parcial('partials/campo', ['nombre' => 'email', 'etiqueta' => 'Correo electrónico', 'tipo' => 'email', 'valor' => $valor('email'), 'error' => $error('email'), 'icono' => 'mail', 'atributos' => 'placeholder="cliente@correo.com"']) ?>
            <?= $this->parcial('partials/campo', ['nombre' => 'telefono', 'etiqueta' => 'Teléfono', 'tipo' => 'tel', 'valor' => $valor('telefono'), 'icono' => 'phone', 'atributos' => 'maxlength="20" placeholder="300 000 0000"']) ?>
            <?= $this->parcial('partials/campo', ['nombre' => 'direccion', 'etiqueta' => 'Dirección', 'valor' => $valor('direccion'), 'icono' => 'map-pin', 'ancho' => 'completo', 'atributos' => 'maxlength="180"']) ?>
        </div>
    </div>

    <?= $this->parcial('partials/form-acciones', ['ruta' => 'clientes', 'editando' => $editando]) ?>
</form>
