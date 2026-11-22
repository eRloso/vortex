<?php
/**
 * Front controller: toda petición entra por aquí.
 * Ejemplo: index.php?r=productos
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/bootstrap.php';

$router = new App\Core\Router();
(require BASE_PATH . '/config/rutas.php')($router);

try {
    $router->despachar($_GET['r'] ?? 'dashboard', $_SERVER['REQUEST_METHOD'] ?? 'GET');
} catch (PDOException $e) {
    http_response_code(500);
    echo (new App\Core\View())->render('errores/error', [
        'mensaje' => 'No fue posible conectar con la base de datos. Verifica config/config.php e importa database/vortex.sql.',
        'detalle' => App\Core\Config::get('app.debug') ? $e->getMessage() : null,
    ], null);
}
