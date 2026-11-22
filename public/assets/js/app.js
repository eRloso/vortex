/**
 * Vortex · utilidades globales de la interfaz.
 * Expone window.Vortex con helpers reutilizables por los demás scripts.
 */
const Vortex = (() => {
    const formatoCop = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 });
    const formatoUsd = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' });

    const iconos = { exito: 'circle-check', error: 'circle-alert', info: 'info', alerta: 'triangle-alert' };

    /** Escapa texto antes de insertarlo como HTML. */
    const esc = (texto) => String(texto ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    /** Vuelve a dibujar los íconos Lucide (tras insertar HTML dinámico). */
    const iconizar = () => window.lucide?.createIcons();

    const cerrarAlerta = (alerta) => {
        alerta.classList.add('saliendo');
        setTimeout(() => alerta.remove(), 300);
    };

    /** Notificación flotante. */
    const toast = (mensaje, tipo = 'info') => {
        const alerta = document.createElement('div');
        alerta.className = `alerta alerta--${tipo}`;
        alerta.innerHTML = `<i data-lucide="${iconos[tipo] ?? 'info'}"></i><span>${esc(mensaje)}</span>`;
        document.getElementById('toasts')?.appendChild(alerta);
        iconizar();
        setTimeout(() => cerrarAlerta(alerta), 4500);
    };

    /** Cliente para la API REST interna (index.php?r=api/...). */
    class Api {
        constructor(csrf = '') {
            this.csrf = csrf;
        }

        url(ruta, parametros = {}) {
            const url = new URL('index.php', window.location.href);
            url.searchParams.set('r', ruta);
            Object.entries(parametros).forEach(([clave, valor]) => url.searchParams.set(clave, valor));
            return url;
        }

        async get(ruta, parametros = {}) {
            return this.procesar(await fetch(this.url(ruta, parametros), { headers: { Accept: 'application/json' } }));
        }

        async post(ruta, cuerpo = {}) {
            return this.procesar(await fetch(this.url(ruta), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-Token': this.csrf },
                body: JSON.stringify(cuerpo),
            }));
        }

        async procesar(respuesta) {
            const datos = await respuesta.json().catch(() => ({}));
            if (!respuesta.ok || datos.ok === false) {
                throw new Error(datos.mensaje || 'Error de comunicación con el servidor.');
            }
            return datos;
        }
    }

    /* ---------- Comportamientos globales ---------- */
    const iniciar = () => {
        iconizar();

        // Tema claro / oscuro
        document.querySelector('[data-cambiar-tema]')?.addEventListener('click', () => {
            const html = document.documentElement;
            html.dataset.tema = html.dataset.tema === 'oscuro' ? 'claro' : 'oscuro';
            try { localStorage.setItem('vortex-tema', html.dataset.tema); } catch (e) { /* almacenamiento no disponible */ }
            document.dispatchEvent(new CustomEvent('vortex:tema'));
        });

        // Menú lateral en móvil
        const sidebar = document.getElementById('sidebar');
        const fondo = document.querySelector('[data-cerrar-menu]');
        const alternarMenu = (abrir) => {
            sidebar?.classList.toggle('abierto', abrir);
            fondo?.classList.toggle('visible', abrir);
        };
        document.querySelector('[data-abrir-menu]')?.addEventListener('click', () => alternarMenu(true));
        fondo?.addEventListener('click', () => alternarMenu(false));

        // Alertas flash: cierre manual y automático
        document.querySelectorAll('[data-cerrar-alerta]').forEach((b) => b.addEventListener('click', () => cerrarAlerta(b.closest('.alerta'))));
        document.querySelectorAll('[data-autocerrar]').forEach((a) => setTimeout(() => cerrarAlerta(a), 6000));

        // Confirmación antes de eliminar
        document.querySelectorAll('form[data-confirmar]').forEach((form) => {
            form.addEventListener('submit', (evento) => {
                if (!window.confirm(form.dataset.confirmar)) evento.preventDefault();
            });
        });

        // Filas de tabla clicables
        document.querySelectorAll('tr[data-href]').forEach((fila) => {
            fila.addEventListener('click', () => { window.location.href = fila.dataset.href; });
        });

        // Selector de color en categorías
        const entradaColor = document.querySelector('[data-color-entrada]');
        document.querySelectorAll('[data-color]').forEach((muestra) => {
            muestra.addEventListener('click', () => { if (entradaColor) entradaColor.value = muestra.dataset.color; });
        });

        // Atajo F2: nueva venta
        document.addEventListener('keydown', (evento) => {
            if (evento.key === 'F2') {
                evento.preventDefault();
                window.location.href = 'index.php?r=ventas/nueva';
            }
        });
    };

    document.addEventListener('DOMContentLoaded', iniciar);

    return { esc, toast, iconizar, Api, formatoCop, formatoUsd };
})();

window.Vortex = Vortex;
