<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Venta;
use App\Services\TasaCambioService;
use InvalidArgumentException;
use PDOException;
use RuntimeException;

/**
 * API REST interna. Todas las respuestas siguen el formato:
 *   { "ok": true,  "datos": ... }
 *   { "ok": false, "mensaje": "..." }
 */
class ApiController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        if (Session::usuario() === null) {
            $this->json(['ok' => false, 'mensaje' => 'No autenticado.'], 401);
        }
    }

    /** GET api/productos&q=texto */
    public function productos(): void
    {
        $productos = array_map([$this, 'formatearProducto'], (new Producto())->listar($this->input('q')));
        $this->json(['ok' => true, 'total' => count($productos), 'datos' => $productos]);
    }

    /** GET api/clientes&q=texto */
    public function clientes(): void
    {
        $clientes = array_map(fn (array $c) => [
            'id'        => (int) $c['id'],
            'documento' => $c['documento'],
            'nombre'    => $c['nombre'],
            'email'     => $c['email'],
        ], (new Cliente())->listar($this->input('q')));

        $this->json(['ok' => true, 'total' => count($clientes), 'datos' => $clientes]);
    }

    /** GET api/tasa — tasa COP→USD obtenida de la API externa */
    public function tasa(): void
    {
        $servicio = new TasaCambioService();
        $this->json(['ok' => true, 'datos' => $servicio->obtener() + ['pesos_por_dolar' => round($servicio->pesosPorDolar(), 2)]]);
    }

    /** GET api/estadisticas — datos para gráficos */
    public function estadisticas(): void
    {
        $ventas = new Venta();
        $this->json(['ok' => true, 'datos' => [
            'resumen' => $ventas->resumen(),
            'por_dia' => $ventas->porDia(7),
            'top'     => $ventas->topProductos(5),
        ]]);
    }

    /** POST api/ventas — body JSON: { cliente_id, metodo_pago, items: [{id, cantidad}] } */
    public function registrarVenta(): void
    {
        if (!$this->csrfValido()) {
            $this->json(['ok' => false, 'mensaje' => 'Token de seguridad inválido. Recarga la página.'], 419);
        }

        $cuerpo    = $this->cuerpoJson();
        $clienteId = (int) ($cuerpo['cliente_id'] ?? Cliente::CONSUMIDOR_FINAL);

        try {
            if ((new Cliente())->buscar($clienteId) === null) {
                throw new InvalidArgumentException('El cliente seleccionado no existe.');
            }

            $ventaId = (new Venta())->registrar(
                (array) ($cuerpo['items'] ?? []),
                $clienteId,
                Session::usuario()['id'],
                (string) ($cuerpo['metodo_pago'] ?? 'efectivo'),
                (new TasaCambioService())->obtener()['tasa']
            );

            Session::flash('exito', 'Venta ' . Venta::generarCodigo($ventaId) . ' registrada con éxito.');
            $this->json(['ok' => true, 'id' => $ventaId, 'url' => url('ventas/detalle', ['id' => $ventaId])], 201);
        } catch (PDOException $e) {
            $this->json(['ok' => false, 'mensaje' => 'Error en la base de datos al registrar la venta.'], 500);
        } catch (InvalidArgumentException | RuntimeException $e) {
            $this->json(['ok' => false, 'mensaje' => $e->getMessage()], 422);
        }
    }

    private function formatearProducto(array $p): array
    {
        return [
            'id'              => (int) $p['id'],
            'codigo'          => $p['codigo'],
            'nombre'          => $p['nombre'],
            'precio'          => (float) $p['precio'],
            'stock'           => (int) $p['stock'],
            'stock_minimo'    => (int) $p['stock_minimo'],
            'categoria'       => $p['categoria'] ?? 'Sin categoría',
            'categoria_color' => $p['categoria_color'] ?? '#8C95B3',
        ];
    }
}
