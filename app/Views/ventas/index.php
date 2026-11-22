<form class="tarjeta filtros" method="get">
    <input type="hidden" name="r" value="ventas">
    <label class="campo">
        <span class="campo-etiqueta">Buscar</span>
        <span class="campo-control con-icono"><i data-lucide="search"></i><input type="search" name="q" value="<?= e($filtros['q']) ?>" placeholder="Código o cliente"></span>
    </label>
    <label class="campo">
        <span class="campo-etiqueta">Desde</span>
        <span class="campo-control"><input type="date" name="desde" value="<?= e($filtros['desde']) ?>"></span>
    </label>
    <label class="campo">
        <span class="campo-etiqueta">Hasta</span>
        <span class="campo-control"><input type="date" name="hasta" value="<?= e($filtros['hasta']) ?>"></span>
    </label>
    <label class="campo">
        <span class="campo-etiqueta">Método de pago</span>
        <span class="campo-control">
            <select name="metodo">
                <option value="">Todos</option>
                <?php foreach ($metodos as $clave => $texto): ?>
                    <option value="<?= e($clave) ?>" <?= $filtros['metodo'] === $clave ? 'selected' : '' ?>><?= e($texto) ?></option>
                <?php endforeach; ?>
            </select>
        </span>
    </label>
    <div class="filtros-acciones">
        <button class="btn btn--primario"><i data-lucide="filter"></i>Filtrar</button>
        <a href="<?= url('ventas') ?>" class="btn btn--fantasma">Limpiar</a>
    </div>
</form>

<section class="tarjeta">
    <header class="tarjeta-cabecera">
        <div>
            <h2><?= count($ventas) ?> venta(s)</h2>
            <p>Total del periodo: <strong class="texto-acento mono"><?= dinero($totalFiltrado) ?></strong></p>
        </div>
        <a href="<?= url('ventas/nueva') ?>" class="btn btn--primario"><i data-lucide="plus"></i>Nueva venta</a>
    </header>

    <?php if (!$ventas): ?>
        <?= $this->parcial('partials/vacio', ['icono' => 'receipt', 'titulo' => 'No hay ventas', 'texto' => 'No se encontraron ventas con los filtros aplicados.']) ?>
    <?php else: ?>
        <div class="tabla-envoltura">
            <table class="tabla">
                <thead>
                <tr>
                    <th>Código</th><th>Cliente</th><th>Vendedor</th><th class="centro">Unidades</th>
                    <th>Método</th><th>Fecha</th><th class="derecha">Total</th><th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($ventas as $v): ?>
                    <tr class="fila-enlace" data-href="<?= url('ventas/detalle', ['id' => $v['id']]) ?>">
                        <td class="mono"><strong><?= e($v['codigo']) ?></strong></td>
                        <td><?= e($v['cliente'] ?? 'Consumidor final') ?></td>
                        <td class="texto-suave"><?= e($v['vendedor']) ?></td>
                        <td class="centro"><?= (int) $v['unidades'] ?></td>
                        <td><span class="badge badge--<?= e($v['metodo_pago']) ?>"><?= e($metodos[$v['metodo_pago']] ?? $v['metodo_pago']) ?></span></td>
                        <td class="texto-suave"><?= fecha($v['created_at']) ?></td>
                        <td class="derecha mono"><strong><?= dinero($v['total']) ?></strong></td>
                        <td class="derecha"><i data-lucide="chevron-right" class="texto-suave"></i></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
