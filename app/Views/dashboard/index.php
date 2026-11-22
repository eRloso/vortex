<?php
$tarjetas = [
    ['icono' => 'banknote',       'tono' => 'primario', 'etiqueta' => 'Ventas de hoy',   'valor' => dinero($resumen['total_hoy']), 'detalle' => $resumen['ventas_hoy'] . ' transacción(es)'],
    ['icono' => 'trending-up',    'tono' => 'acento',   'etiqueta' => 'Ingresos del mes', 'valor' => dinero($resumen['total_mes']), 'detalle' => $resumen['ventas_mes'] . ' venta(s) este mes'],
    ['icono' => 'shopping-basket','tono' => 'exito',    'etiqueta' => 'Ticket promedio', 'valor' => dinero($resumen['ticket_promedio']), 'detalle' => 'Promedio por venta del mes'],
    ['icono' => 'package',        'tono' => 'alerta',   'etiqueta' => 'Productos activos', 'valor' => $totalProductos, 'detalle' => count($stockBajo) . ' con stock bajo · ' . $totalClientes . ' clientes'],
];
$maxTop = max(array_map(fn ($t) => (int) $t['unidades'], $top) ?: [1]);
$fuentes = ['api' => 'API en vivo', 'cache' => 'Caché local', 'respaldo' => 'Tasa de respaldo'];
?>

<section class="stats">
    <?php foreach ($tarjetas as $t): ?>
        <article class="tarjeta stat stat--<?= $t['tono'] ?>">
            <span class="stat-icono"><i data-lucide="<?= $t['icono'] ?>"></i></span>
            <div>
                <p class="stat-etiqueta"><?= e($t['etiqueta']) ?></p>
                <p class="stat-valor"><?= e($t['valor']) ?></p>
                <p class="stat-detalle"><?= e($t['detalle']) ?></p>
            </div>
        </article>
    <?php endforeach; ?>
</section>

<section class="rejilla-dashboard">
    <article class="tarjeta panel-grafico">
        <header class="tarjeta-cabecera">
            <div>
                <h2>Ventas de los últimos 7 días</h2>
                <p>Total facturado por día (COP)</p>
            </div>
            <span class="chip chip--acento"><i data-lucide="activity"></i>Semana actual</span>
        </header>
        <div class="grafico"><canvas id="grafico-ventas"></canvas></div>
        <script type="application/json" id="datos-grafico"><?= json_encode($porDia, JSON_UNESCAPED_UNICODE) ?></script>
    </article>

    <article class="tarjeta tasa">
        <header class="tarjeta-cabecera">
            <div>
                <h2>Dólar hoy</h2>
                <p>Tasa obtenida de open.er-api.com</p>
            </div>
            <span class="chip <?= $tasa['fuente'] === 'respaldo' ? 'chip--alerta' : 'chip--exito' ?>">
                <span class="punto"></span><?= e($fuentes[$tasa['fuente']] ?? $tasa['fuente']) ?>
            </span>
        </header>
        <p class="tasa-valor"><small>1 USD =</small> <?= dinero($pesosPorDolar) ?></p>
        <dl class="tasa-datos">
            <div><dt>Ventas del mes en USD</dt><dd><?= dinero($resumen['total_mes'] * $tasa['tasa'], 'USD') ?></dd></div>
            <div><dt>Última actualización</dt><dd><?= fecha($tasa['actualizado']) ?></dd></div>
        </dl>
        <button class="btn btn--fantasma btn--sm" data-actualizar-tasa><i data-lucide="refresh-cw"></i>Consultar API</button>
    </article>

    <article class="tarjeta">
        <header class="tarjeta-cabecera">
            <div><h2>Más vendidos</h2><p>Unidades vendidas históricamente</p></div>
        </header>
        <?php if (!$top): ?>
            <p class="texto-suave">Todavía no hay ventas registradas.</p>
        <?php else: ?>
            <ol class="ranking">
                <?php foreach ($top as $i => $t): ?>
                    <li>
                        <span class="ranking-posicion"><?= $i + 1 ?></span>
                        <div class="ranking-info">
                            <div class="ranking-fila">
                                <strong><?= e($t['nombre']) ?></strong>
                                <span class="mono"><?= (int) $t['unidades'] ?> und.</span>
                            </div>
                            <div class="barra"><span style="width: <?= round((int) $t['unidades'] / $maxTop * 100) ?>%"></span></div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </article>

    <article class="tarjeta">
        <header class="tarjeta-cabecera">
            <div><h2>Alertas de inventario</h2><p>Productos en o bajo el mínimo</p></div>
            <a href="<?= url('productos') ?>" class="enlace">Ver todos</a>
        </header>
        <?php if (!$stockBajo): ?>
            <div class="todo-bien"><i data-lucide="shield-check"></i>Todo el inventario está en niveles saludables.</div>
        <?php else: ?>
            <ul class="lista-alertas">
                <?php foreach ($stockBajo as $p): ?>
                    <li>
                        <span class="miniatura miniatura--sm" style="--c: <?= e($p['categoria_color'] ?? '#F59E0B') ?>"><?= e(iniciales($p['nombre'])) ?></span>
                        <span class="lista-alertas-nombre"><?= e($p['nombre']) ?><small class="mono"><?= e($p['codigo']) ?></small></span>
                        <span class="stock stock--bajo"><?= (int) $p['stock'] ?> / <?= (int) $p['stock_minimo'] ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>

    <article class="tarjeta panel-ancho">
        <header class="tarjeta-cabecera">
            <div><h2>Últimas ventas</h2><p>Movimientos más recientes</p></div>
            <a href="<?= url('ventas') ?>" class="enlace">Ver historial</a>
        </header>
        <?php if (!$recientes): ?>
            <?= $this->parcial('partials/vacio', ['icono' => 'receipt', 'titulo' => 'Sin ventas', 'texto' => 'Registra tu primera venta desde el punto de venta.', 'accion' => ['url' => url('ventas/nueva'), 'texto' => 'Nueva venta']]) ?>
        <?php else: ?>
            <div class="tabla-envoltura">
                <table class="tabla tabla--compacta">
                    <thead><tr><th>Código</th><th>Cliente</th><th>Método</th><th>Fecha</th><th class="derecha">Total</th></tr></thead>
                    <tbody>
                    <?php foreach ($recientes as $v): ?>
                        <tr class="fila-enlace" data-href="<?= url('ventas/detalle', ['id' => $v['id']]) ?>">
                            <td class="mono"><strong><?= e($v['codigo']) ?></strong></td>
                            <td><?= e($v['cliente'] ?? 'Consumidor final') ?></td>
                            <td><span class="badge badge--<?= e($v['metodo_pago']) ?>"><?= e(ucfirst($v['metodo_pago'])) ?></span></td>
                            <td class="texto-suave"><?= fecha($v['created_at']) ?></td>
                            <td class="derecha mono"><strong><?= dinero($v['total']) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </article>
</section>
