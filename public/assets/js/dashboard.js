/**
 * Vortex · Dashboard: gráfico de ventas (Chart.js) y consulta de la tasa del dólar vía API.
 */
class GraficoVentas {
    constructor(canvas, datos) {
        this.canvas = canvas;
        this.datos = datos;
        this.grafico = null;
        this.dibujar();
        document.addEventListener('vortex:tema', () => this.dibujar());
    }

    colores() {
        const estilos = getComputedStyle(document.documentElement);
        const leer = (variable) => estilos.getPropertyValue(variable).trim();
        return {
            primario: leer('--vx-primario'),
            acento: leer('--vx-acento'),
            texto: leer('--vx-texto-suave'),
            borde: leer('--vx-borde'),
        };
    }

    dibujar() {
        if (!window.Chart) return;
        this.grafico?.destroy();

        const c = this.colores();
        const contexto = this.canvas.getContext('2d');
        const relleno = contexto.createLinearGradient(0, 0, 0, this.canvas.clientHeight || 290);
        relleno.addColorStop(0, c.primario + '66');
        relleno.addColorStop(1, c.primario + '00');

        this.grafico = new Chart(contexto, {
            type: 'line',
            data: {
                labels: this.datos.map((d) => d.etiqueta),
                datasets: [{
                    label: 'Ventas',
                    data: this.datos.map((d) => d.total),
                    borderColor: c.primario,
                    backgroundColor: relleno,
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointRadius: 4,
                    pointHoverRadius: 7,
                    pointBackgroundColor: c.acento,
                    pointBorderColor: c.primario,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        padding: 12,
                        callbacks: {
                            label: (item) => {
                                const dia = this.datos[item.dataIndex];
                                return ` ${Vortex.formatoCop.format(item.raw)} · ${dia.cantidad} venta(s)`;
                            },
                        },
                    },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: c.texto }, border: { display: false } },
                    y: {
                        beginAtZero: true,
                        grid: { color: c.borde },
                        border: { display: false },
                        ticks: {
                            color: c.texto,
                            callback: (valor) => '$' + Intl.NumberFormat('es-CO', { notation: 'compact' }).format(valor),
                        },
                    },
                },
            },
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('grafico-ventas');
    const fuente = document.getElementById('datos-grafico');
    if (canvas && fuente) new GraficoVentas(canvas, JSON.parse(fuente.textContent));

    // Consulta en vivo de la API de tasa de cambio
    const boton = document.querySelector('[data-actualizar-tasa]');
    boton?.addEventListener('click', async () => {
        boton.classList.add('girando');
        try {
            const { datos } = await new Vortex.Api().get('api/tasa');
            const valor = document.querySelector('.tasa-valor');
            if (valor) valor.innerHTML = `<small>1 USD =</small> ${Vortex.formatoCop.format(datos.pesos_por_dolar)}`;
            Vortex.toast(`Tasa consultada (${datos.fuente === 'api' ? 'API en vivo' : datos.fuente}).`, 'exito');
        } catch (error) {
            Vortex.toast(error.message, 'error');
        } finally {
            boton.classList.remove('girando');
        }
    });
});
