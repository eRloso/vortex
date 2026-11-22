<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Venta;
use App\Services\TasaCambioService;

class DashboardController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requiereAutenticacion();
    }

    public function index(): void
    {
        $ventas    = new Venta();
        $productos = new Producto();
        $servicioTasa = new TasaCambioService();

        $this->render('dashboard/index', [
            'titulo'       => 'Dashboard',
            'subtitulo'    => 'Resumen general de tu negocio',
            'resumen'      => $ventas->resumen(),
            'porDia'       => $ventas->porDia(7),
            'top'          => $ventas->topProductos(5),
            'recientes'    => $ventas->recientes(6),
            'stockBajo'    => $productos->stockBajo(),
            'totalProductos' => $productos->contarActivos(),
            'totalClientes'  => (new Cliente())->contar(),
            'tasa'         => $servicioTasa->obtener(),
            'pesosPorDolar'=> $servicioTasa->pesosPorDolar(),
            'scripts'      => ['https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js', 'js/dashboard.js'],
        ]);
    }
}
