<?= $this->parcial('partials/buscador', ['ruta' => 'categorias', 'q' => $q, 'placeholder' => 'Buscar categoría...', 'textoNuevo' => 'Nueva categoría']) ?>

<?php if (!$registros): ?>
    <section class="tarjeta">
        <?= $this->parcial('partials/vacio', [
            'icono'  => 'tags',
            'titulo' => 'No hay categorías',
            'texto'  => 'Organiza tus productos creando categorías con su propio color.',
            'accion' => ['url' => url('categorias/nuevo'), 'texto' => 'Nueva categoría'],
        ]) ?>
    </section>
<?php else: ?>
    <section class="rejilla-categorias">
        <?php foreach ($registros as $c): ?>
            <article class="tarjeta categoria" style="--c: <?= e($c['color']) ?>">
                <span class="categoria-icono"><i data-lucide="tag"></i></span>
                <div class="categoria-datos">
                    <h3><?= e($c['nombre']) ?></h3>
                    <p><?= (int) $c['total_productos'] ?> producto(s) · <span class="mono"><?= e($c['color']) ?></span></p>
                </div>
                <?= $this->parcial('partials/acciones', ['ruta' => 'categorias', 'id' => $c['id'], 'nombre' => $c['nombre'], 'csrf' => $csrf]) ?>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
