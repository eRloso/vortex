<?php
/**
 * Tabla de rutas: 'ruta' => [Controlador::class, 'metodo'].
 * Se accede como index.php?r=ruta
 */

use App\Core\Router;
use App\Controllers\ApiController;
use App\Controllers\AuthController;
use App\Controllers\CategoriaController;
use App\Controllers\ClienteController;
use App\Controllers\DashboardController;
use App\Controllers\ProductoController;
use App\Controllers\VentaController;

return function (Router $router): void {
    // Autenticación
    $router->get('login', [AuthController::class, 'formulario'])
           ->post('login', [AuthController::class, 'ingresar'])
           ->get('logout', [AuthController::class, 'salir']);

    // Panel
    $router->get('dashboard', [DashboardController::class, 'index']);

    // CRUDs (comparten la misma estructura gracias a CrudController)
    $crud = [
        'productos'  => ProductoController::class,
        'categorias' => CategoriaController::class,
        'clientes'   => ClienteController::class,
    ];
    foreach ($crud as $prefijo => $controlador) {
        $router->get($prefijo, [$controlador, 'index'])
               ->get("$prefijo/nuevo", [$controlador, 'nuevo'])
               ->get("$prefijo/editar", [$controlador, 'editar'])
               ->post("$prefijo/guardar", [$controlador, 'guardar'])
               ->post("$prefijo/eliminar", [$controlador, 'eliminar']);
    }

    // Ventas
    $router->get('ventas', [VentaController::class, 'index'])
           ->get('ventas/nueva', [VentaController::class, 'nueva'])
           ->get('ventas/detalle', [VentaController::class, 'detalle']);

    // API REST interna (JSON)
    $router->get('api/productos', [ApiController::class, 'productos'])
           ->get('api/clientes', [ApiController::class, 'clientes'])
           ->get('api/tasa', [ApiController::class, 'tasa'])
           ->get('api/estadisticas', [ApiController::class, 'estadisticas'])
           ->post('api/ventas', [ApiController::class, 'registrarVenta']);
};
