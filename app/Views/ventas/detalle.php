<?php $tasa = (float) $venta['tasa_usd']; ?>
<div class="barra-herramientas no-imprimir">
    <a href="<?= url('ventas') ?>" class="btn btn--fantasma"><i data-lucide="arrow-left"></i>Volver al historial</a>
    <div class="grupo-botones">
        <button class="btn btn--fantasma" onclick="window.print()"><i data-lucide="printer"></i>Imprimir</button>
        <a href="<?= url('ventas/nueva') ?>" class="btn btn--primario"><i data-lucide="plus"></i>Nueva venta</a>
    </div>
</div>

<article class="tarjeta factura">
    <header class="factura-cabecera">
        <div class="marca">
            <img src="<?= asset('img/logo.svg') ?>" alt="" class="marca-logo">
            <span class="marca-texto">Vortex<small>Sistema de ventas</small></span>
        </div>
        <div class="factura-codigo">
            <span class="texto-suave">Comprobante</span>
            <strong class="mono"><?= e($venta['codigo']) ?></strong>
            <span class="badge badge--<?= e($venta['metodo_pago']) ?>"><?= e($metodos[$venta['metodo_pago']] ?? $venta['metodo_pago']) ?></span>
        </div>
    </header>

    <section class="factura-partes">
        <div>
            <h3>Cliente</h3>
            <p><strong><?= e($venta['cliente'] ?? 'Consumidor final') ?></strong></p>
            <?php if (!empty($venta['documento'])): ?><p class="texto-suave">Documento: <?= e($venta['documento']) ?></p><?php endif; ?>
            <?php if (!empty($venta['cliente_email'])): ?><p class="texto-suave"><?= e($venta['cliente_email']) ?></p><?php endif; ?>
            <?php if (!empty($venta['cliente_direccion'])): ?><p class="texto-suave"><?= e($venta['cliente_direccion']) ?></p><?php endif; ?>
        </div>
        <div>
            <h3>Detalles</h3>
            <p>Fecha: <strong><?= fecha($venta['created_at']) ?></strong></p>
            <p class="texto-suave">Atendido por: <?= e($venta['vendedor']) ?></p>
        </div>
    </section>

    <div class="tabla-envoltura">
        <table class="tabla">
            <thead><tr><th>Producto</th><th class="centro">Cant.</th><th class="derecha">Precio unit.</th><th class="derecha">Importe</th></tr></thead>
            <tbody>
            <?php foreach ($venta['items'] as $item): ?>
                <tr>
                    <td><strong><?= e($item['nombre']) ?></strong><br><small class="mono texto-suave"><?= e($item['codigo']) ?></small></td>
                    <td class="centro"><?= (int) $item['cantidad'] ?></td>
                    <td class="derecha mono"><?= dinero($item['precio_unitario']) ?></td>
                    <td class="derecha mono"><?= dinero($item['importe']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <footer class="factura-pie">
        <p class="texto-suave factura-nota">
            <i data-lucide="globe"></i>
            Tasa aplicada al momento de la venta: 1 USD = <?= $tasa > 0 ? dinero(1 / $tasa) : '—' ?>
        </p>
        <dl class="pos-totales">
            <div><dt>Subtotal</dt><dd><?= dinero($venta['subtotal']) ?></dd></div>
            <div><dt>IVA (<?= round($iva * 100) ?>%)</dt><dd><?= dinero($venta['impuesto']) ?></dd></div>
            <div class="pos-total"><dt>Total</dt><dd><?= dinero($venta['total']) ?></dd></div>
            <div class="pos-usd"><dt>Equivalente USD</dt><dd><?= dinero($venta['total'] * $tasa, 'USD') ?></dd></div>
        </dl>
    </footer>

    <p class="factura-gracias">¡Gracias por tu compra!</p>
</article>
