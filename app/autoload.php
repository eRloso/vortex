<?php
/**
 * Autoload PSR-4 simple: App\Models\Venta -> app/Models/Venta.php
 * Lo usan la aplicación (bootstrap.php) y la suite de pruebas (tests/run_tests.php).
 */
spl_autoload_register(function (string $clase): void {
    $prefijo = 'App\\';
    if (strncmp($clase, $prefijo, strlen($prefijo)) !== 0) {
        return;
    }
    $archivo = BASE_PATH . '/app/' . str_replace('\\', '/', substr($clase, strlen($prefijo))) . '.php';
    if (is_file($archivo)) {
        require $archivo;
    }
});
