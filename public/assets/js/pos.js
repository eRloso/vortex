/**
 * Vortex · Punto de venta.
 * Consume la API interna para buscar productos y registrar la venta.
 */

/** Modelo del carrito: solo lógica, sin DOM. */
class Carrito {
    constructor() {
        this.items = new Map();
    }

    agregar(producto) {
        const actual = this.items.get(producto.id);
        const cantidad = (actual?.cantidad ?? 0) + 1;
        if (cantidad > producto.stock) return false;
        this.items.set(producto.id, { ...producto, cantidad });
        return true;
    }

    cambiarCantidad(id, delta) {
        const item = this.items.get(id);
        if (!item) return false;
        const cantidad = item.cantidad + delta;
        if (cantidad <= 0) return this.quitar(id);
        if (cantidad > item.stock) return false;
        item.cantidad = cantidad;
        return true;
    }

    quitar(id) {
        return this.items.delete(id);
    }

    vaciar() {
        this.items.clear();
    }

    get vacio() {
        return this.items.size === 0;
    }

    get unidades() {
        return [...this.items.values()].reduce((suma, item) => suma + item.cantidad, 0);
    }

    get subtotal() {
        return [...this.items.values()].reduce((suma, item) => suma + item.precio * item.cantidad, 0);
    }

    /** Solo se envía id y cantidad: los precios los calcula el servidor. */
    paraEnviar() {
        return [...this.items.values()].map(({ id, cantidad }) => ({ id, cantidad }));
    }
}

/** Controlador de la pantalla: conecta Carrito, API y DOM. */
class PuntoVenta {
    constructor(raiz) {
        this.iva = parseFloat(raiz.dataset.iva) || 0;
        this.tasaUsd = parseFloat(raiz.dataset.tasa) || 0;
        this.api = new Vortex.Api(raiz.dataset.csrf);
        this.carrito = new Carrito();
        this.productos = new Map();
        this.temporizador = null;

        this.el = {
            buscar: document.getElementById('pos-buscar'),
            productos: document.getElementById('pos-productos'),
            items: document.getElementById('pos-items'),
            contador: document.getElementById('pos-contador'),
            subtotal: document.getElementById('pos-subtotal'),
            impuesto: document.getElementById('pos-impuesto'),
            total: document.getElementById('pos-total'),
            usd: document.getElementById('pos-usd'),
            cliente: document.getElementById('pos-cliente'),
            cobrar: document.getElementById('pos-cobrar'),
            vaciar: document.getElementById('pos-vaciar'),
        };

        this.registrarEventos();
        this.buscar('');
        this.pintarCarrito();
    }

    registrarEventos() {
        this.el.buscar.addEventListener('input', () => {
            clearTimeout(this.temporizador);
            this.temporizador = setTimeout(() => this.buscar(this.el.buscar.value.trim()), 280);
        });

        // Enter agrega el primer resultado (útil con lector de código de barras)
        this.el.buscar.addEventListener('keydown', (evento) => {
            if (evento.key !== 'Enter') return;
            evento.preventDefault();
            const primero = this.productos.values().next().value;
            if (primero) this.agregar(primero.id);
        });

        document.addEventListener('keydown', (evento) => {
            if (evento.key === '/' && document.activeElement !== this.el.buscar) {
                evento.preventDefault();
                this.el.buscar.focus();
            }
        });

        this.el.productos.addEventListener('click', (evento) => {
            const tarjeta = evento.target.closest('[data-producto]');
            if (tarjeta) this.agregar(Number(tarjeta.dataset.producto));
        });

        this.el.items.addEventListener('click', (evento) => {
            const boton = evento.target.closest('[data-accion]');
            if (!boton) return;
            const id = Number(boton.closest('[data-item]').dataset.item);
            const acciones = {
                sumar: () => this.carrito.cambiarCantidad(id, 1) || Vortex.toast('No hay más unidades disponibles.', 'alerta'),
                restar: () => this.carrito.cambiarCantidad(id, -1),
                quitar: () => this.carrito.quitar(id),
            };
            acciones[boton.dataset.accion]?.();
            this.actualizar();
        });

        this.el.vaciar.addEventListener('click', () => {
            this.carrito.vaciar();
            this.actualizar();
        });

        this.el.cobrar.addEventListener('click', () => this.cobrar());
    }

    async buscar(termino) {
        this.el.productos.innerHTML = '<div class="esqueleto"></div>'.repeat(6);
        try {
            const { datos } = await this.api.get('api/productos', { q: termino });
            this.productos = new Map(datos.map((p) => [p.id, p]));
            this.pintarProductos();
        } catch (error) {
            this.el.productos.innerHTML = '';
            Vortex.toast(error.message, 'error');
        }
    }

    agregar(id) {
        const producto = this.productos.get(id);
        if (!producto) return;
        if (!this.carrito.agregar(producto)) {
            Vortex.toast(`Stock máximo alcanzado para «${producto.nombre}».`, 'alerta');
            return;
        }
        this.actualizar();
    }

    actualizar() {
        this.pintarCarrito();
        this.pintarProductos();
    }

    pintarProductos() {
        if (this.productos.size === 0) {
            this.el.productos.innerHTML = `
                <div class="tarjeta vacio" style="grid-column: 1 / -1">
                    <span class="vacio-icono"><i data-lucide="search-x"></i></span>
                    <h3>Sin resultados</h3><p>Prueba con otro nombre o código.</p>
                </div>`;
            Vortex.iconizar();
            return;
        }

        this.el.productos.innerHTML = [...this.productos.values()].map((p) => {
            const enCarrito = this.carrito.items.get(p.id)?.cantidad ?? 0;
            const disponible = p.stock - enCarrito;
            return `
                <button class="producto-card ${enCarrito ? 'en-carrito' : ''}" data-producto="${p.id}" style="--c: ${Vortex.esc(p.categoria_color)}" ${disponible <= 0 ? 'disabled' : ''}>
                    <span class="producto-card-cabecera">
                        <span class="badge" style="--c: ${Vortex.esc(p.categoria_color)}">${Vortex.esc(p.categoria)}</span>
                        ${enCarrito ? `<span class="badge badge--neutro">×${enCarrito}</span>` : ''}
                    </span>
                    <h3>${Vortex.esc(p.nombre)}</h3>
                    <span class="producto-card-pie">
                        <span>
                            <span class="producto-card-precio">${Vortex.formatoCop.format(p.precio)}</span><br>
                            <small class="mono">${Vortex.esc(p.codigo)} · ${disponible} disp.</small>
                        </span>
                    </span>
                    <span class="producto-card-agregar"><i data-lucide="plus"></i></span>
                </button>`;
        }).join('');
        Vortex.iconizar();
    }

    pintarCarrito() {
        const { carrito, el } = this;

        el.items.innerHTML = carrito.vacio
            ? `<div class="vacio">
                   <span class="vacio-icono"><i data-lucide="shopping-basket"></i></span>
                   <p>Haz clic en un producto para agregarlo.</p>
               </div>`
            : [...carrito.items.values()].map((item) => `
                <div class="item-carrito" data-item="${item.id}">
                    <div>
                        <p class="item-carrito-nombre">${Vortex.esc(item.nombre)}</p>
                        <p class="item-carrito-precio">${Vortex.formatoCop.format(item.precio)} c/u</p>
                    </div>
                    <span class="item-carrito-importe">${Vortex.formatoCop.format(item.precio * item.cantidad)}</span>
                    <div class="cantidad">
                        <button data-accion="restar" aria-label="Restar"><i data-lucide="minus"></i></button>
                        <span>${item.cantidad}</span>
                        <button data-accion="sumar" aria-label="Sumar"><i data-lucide="plus"></i></button>
                    </div>
                    <button class="item-carrito-quitar" data-accion="quitar" aria-label="Quitar"><i data-lucide="x"></i></button>
                </div>`).join('');

        const subtotal = carrito.subtotal;
        const impuesto = Math.round(subtotal * this.iva * 100) / 100;
        const total = subtotal + impuesto;

        el.contador.textContent = carrito.unidades;
        el.subtotal.textContent = Vortex.formatoCop.format(subtotal);
        el.impuesto.textContent = Vortex.formatoCop.format(impuesto);
        el.total.textContent = Vortex.formatoCop.format(total);
        el.usd.textContent = Vortex.formatoUsd.format(total * this.tasaUsd);
        el.cobrar.disabled = el.vaciar.disabled = carrito.vacio;

        Vortex.iconizar();
    }

    async cobrar() {
        if (this.carrito.vacio) return;

        const boton = this.el.cobrar;
        const textoOriginal = boton.innerHTML;
        boton.disabled = true;
        boton.innerHTML = '<i data-lucide="loader-circle"></i>Procesando...';
        boton.classList.add('girando');
        Vortex.iconizar();

        try {
            const respuesta = await this.api.post('api/ventas', {
                cliente_id: Number(this.el.cliente.value),
                metodo_pago: document.querySelector('input[name="metodo"]:checked')?.value ?? 'efectivo',
                items: this.carrito.paraEnviar(),
            });
            window.location.href = respuesta.url;
        } catch (error) {
            Vortex.toast(error.message, 'error');
            boton.innerHTML = textoOriginal;
            boton.classList.remove('girando');
            boton.disabled = false;
            Vortex.iconizar();
            this.buscar(this.el.buscar.value.trim()); // refresca stock real
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const raiz = document.getElementById('pos');
    if (raiz) new PuntoVenta(raiz);
});
