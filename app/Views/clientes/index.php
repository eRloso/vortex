<?= $this->parcial('partials/buscador', ['ruta' => 'clientes', 'q' => $q, 'placeholder' => 'Buscar por nombre, documento o correo...', 'textoNuevo' => 'Nuevo cliente']) ?>

<section class="tarjeta">
    <?php if (!$registros): ?>
        <?= $this->parcial('partials/vacio', [
            'icono'  => 'users-round',
            'titulo' => $q ? 'Sin coincidencias' : 'Aún no hay clientes',
            'texto'  => $q ? "No encontramos clientes para «{$q}»." : 'Registra a tus clientes para asociarlos a las ventas.',
            'accion' => ['url' => url('clientes/nuevo'), 'texto' => 'Nuevo cliente'],
        ]) ?>
    <?php else: ?>
        <div class="tabla-envoltura">
            <table class="tabla">
                <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Contacto</th>
                    <th class="centro">Compras</th>
                    <th class="derecha">Total comprado</th>
                    <th class="derecha">Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($registros as $c): ?>
                    <tr>
                        <td>
                            <div class="celda-principal">
                                <span class="avatar avatar--sm"><?= e(iniciales($c['nombre'])) ?></span>
                                <span>
                                    <strong><?= e($c['nombre']) ?></strong>
                                    <small class="mono">CC <?= e($c['documento']) ?></small>
                                </span>
                            </div>
                        </td>
                        <td>
                            <div class="contacto">
                                <?php if ($c['email']): ?><span><i data-lucide="mail"></i><?= e($c['email']) ?></span><?php endif; ?>
                                <?php if ($c['telefono']): ?><span><i data-lucide="phone"></i><?= e($c['telefono']) ?></span><?php endif; ?>
                                <?php if (!$c['email'] && !$c['telefono']): ?><span class="texto-suave">—</span><?php endif; ?>
                            </div>
                        </td>
                        <td class="centro"><span class="badge badge--neutro"><?= (int) $c['total_compras'] ?></span></td>
                        <td class="derecha mono"><?= dinero($c['total_gastado']) ?></td>
                        <td class="derecha">
                            <?= $this->parcial('partials/acciones', ['ruta' => 'clientes', 'id' => $c['id'], 'nombre' => $c['nombre'], 'csrf' => $csrf]) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <footer class="tarjeta-pie"><?= count($registros) ?> cliente(s)</footer>
    <?php endif; ?>
</section>
