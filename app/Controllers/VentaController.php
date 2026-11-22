<?php

namespace App\Controllers;

use App\Core\Config;
use App\Core\Controller;
use App\Core\Session;
use App\Models\Cliente;
use App\Models\Venta;
use App\Services\TasaCambioService;

class VentaController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requiereAutenticacion();
    }

    public function index(): void
    {
        $filtros = [
            'q'      => $this->input('q'),
            'desde'  => $this->input('desde'),
            'hasta'  => $this->input('hasta'),
            'metodo' => $this->input('metodo'),
        ];
        $ventas = (new Venta())->listarFiltrado($filtros);

        $this->render('ventas/index', [
            'titulo'    => 'Historial de ventas',
            'subtitulo' => 'Consulta y filtra todas las ventas registradas',
            'ventas'    => $ventas,
            'filtros'   => $filtros,
            'metodos'   => Venta::METODOS_PAGO,
            'totalFiltrado' => array_sum(array_column($ventas, 'total')),
        ]);
    }

    public function nueva(): void
    {
        $this->render('ventas/nueva', [
            'titulo'    => 'Punto de venta',
            'subtitulo' => 'Busca productos, arma el carrito y cobra',
            'clientes'  => (new Cliente())->todos('nombre ASC'),
            'metodos'   => Venta::METODOS_PAGO,
            'tasa'      => (new TasaCambioService())->obtener(),
            'iva'       => (float) Config::get('app.iva'),
            'consumidorFinal' => Cliente::CONSUMIDOR_FINAL,
            'scripts'   => ['js/pos.js'],
        ]);
    }

    public function detalle(): void
    {
        $venta = (new Venta())->conDetalle((int) $this->input('id'));
        if ($venta === null) {
            Session::flash('error', 'La venta solicitada no existe.');
            $this->redirigir('ventas');
        }

        $this->render('ventas/detalle', [
            'titulo'    => 'Venta ' . $venta['codigo'],
            'subtitulo' => 'Comprobante de venta',
            'venta'     => $venta,
            'metodos'   => Venta::METODOS_PAGO,
            'iva'       => (float) Config::get('app.iva'),
        ]);
    }
}
