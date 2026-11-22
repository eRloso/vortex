<div class="pos" id="pos"
     data-iva="<?= e($iva) ?>"
     data-tasa="<?= e($tasa['tasa']) ?>"
     data-csrf="<?= e($csrf) ?>">

    <section class="pos-catalogo">
        <div class="tarjeta pos-busqueda">
            <span class="campo-control con-icono campo-control--grande">
                <i data-lucide="search"></i>
                <input type="search" id="pos-buscar" placeholder="Buscar producto por nombre o código..." autocomplete="off" autofocus>
                <kbd>/</kbd>
            </span>
        </div>
        <div class="pos-productos" id="pos-productos" aria-live="polite">
            <!-- Se llena con la API: index.php?r=api/productos -->
        </div>
    </section>

    <aside class="tarjeta pos-carrito">
        <header class="pos-carrito-cabecera">
            <h2><i data-lucide="shopping-cart"></i>Carrito <span class="badge badge--neutro" id="pos-contador">0</span></h2>
            <button class="btn btn--fantasma btn--sm" id="pos-vaciar" disabled><i data-lucide="trash-2"></i>Vaciar</button>
        </header>

        <div class="pos-items" id="pos-items"></div>

        <div class="pos-opciones">
            <label class="campo">
                <span class="campo-etiqueta">Cliente</span>
                <span class="campo-control con-icono">
                    <i data-lucide="user"></i>
                    <select id="pos-cliente">
                        <?php foreach ($clientes as $c): ?>
                            <option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === $consumidorFinal ? 'selected' : '' ?>>
                                <?= e($c['nombre']) ?> · <?= e($c['documento']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </span>
            </label>

            <div class="campo">
                <span class="campo-etiqueta">Método de pago</span>
                <div class="segmentado" id="pos-metodo">
                    <?php $primero = true; foreach ($metodos as $clave => $texto): ?>
                        <label>
                            <input type="radio" name="metodo" value="<?= e($clave) ?>" <?= $primero ? 'checked' : '' ?>>
                            <span><?= e($texto) ?></span>
                        </label>
                    <?php $primero = false; endforeach; ?>
                </div>
            </div>
        </div>

        <dl class="pos-totales">
            <div><dt>Subtotal</dt><dd id="pos-subtotal">$ 0</dd></div>
            <div><dt>IVA (<?= round($iva * 100) ?>%)</dt><dd id="pos-impuesto">$ 0</dd></div>
            <div class="pos-total"><dt>Total</dt><dd id="pos-total">$ 0</dd></div>
            <div class="pos-usd"><dt><i data-lucide="globe"></i>Equivalente USD</dt><dd id="pos-usd">US$ 0.00</dd></div>
        </dl>

        <button class="btn btn--primario btn--bloque btn--grande" id="pos-cobrar" disabled>
            <i data-lucide="credit-card"></i>Cobrar venta
        </button>
    </aside>
</div>
