<?= $this->parcial('partials/buscador', ['ruta' => 'productos', 'q' => $q, 'placeholder' => 'Buscar por nombre, código o categoría...', 'textoNuevo' => 'Nuevo producto']) ?>

<section class="tarjeta">
    <?php if (!$registros): ?>
        <?= $this->parcial('partials/vacio', [
            'icono'  => 'package-open',
            'titulo' => $q ? 'Sin coincidencias' : 'Aún no hay productos',
            'texto'  => $q ? "No encontramos productos para «{$q}»." : 'Registra tu primer producto para empezar a vender.',
            'accion' => ['url' => url('productos/nuevo'), 'texto' => 'Nuevo producto'],
        ]) ?>
    <?php else: ?>
        <div class="tabla-envoltura">
            <table class="tabla">
                <thead>
                <tr>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th class="derecha">Precio</th>
                    <th class="centro">Stock</th>
                    <th class="derecha">Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($registros as $p):
                    $bajo = (int) $p['stock'] <= (int) $p['stock_minimo'];
                ?>
                    <tr>
                        <td>
                            <div class="celda-principal">
                                <span class="miniatura" style="--c: <?= e($p['categoria_color'] ?? '#8C95B3') ?>"><?= e(iniciales($p['nombre'])) ?></span>
                                <span>
                                    <strong><?= e($p['nombre']) ?></strong>
                                    <small class="mono"><?= e($p['codigo']) ?></small>
                                </span>
                            </div>
                        </td>
                        <td>
                            <?php if ($p['categoria']): ?>
                                <span class="badge" style="--c: <?= e($p['categoria_color']) ?>"><?= e($p['categoria']) ?></span>
                            <?php else: ?>
                                <span class="texto-suave">Sin categoría</span>
                            <?php endif; ?>
                        </td>
                        <td class="derecha mono"><?= dinero($p['precio']) ?></td>
                        <td class="centro">
                            <span class="stock <?= $bajo ? 'stock--bajo' : '' ?>" title="Mínimo: <?= (int) $p['stock_minimo'] ?>">
                                <?php if ($bajo): ?><i data-lucide="triangle-alert"></i><?php endif; ?>
                                <?= (int) $p['stock'] ?> und.
                            </span>
                        </td>
                        <td class="derecha">
                            <?= $this->parcial('partials/acciones', ['ruta' => 'productos', 'id' => $p['id'], 'nombre' => $p['nombre'], 'csrf' => $csrf]) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <footer class="tarjeta-pie"><?= count($registros) ?> producto(s) activos</footer>
    <?php endif; ?>
</section>
