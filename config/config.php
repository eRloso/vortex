<?php
/**
 * Configuración central de Vortex.
 * Todos los valores se leen con Config::get('seccion.clave').
 *
 * En local (XAMPP) se usan los valores por defecto.
 * En producción (EthioDeploy u otro hosting) se leen variables de entorno:
 *   DATABASE_URL = mysql://usuario:clave@host:puerto/base   (lo inyecta el addon MySQL)
 *   APP_DEBUG    = false
 */

$env = fn (string $clave, $defecto = null) => getenv($clave) !== false ? getenv($clave) : $defecto;

$url = $env('DATABASE_URL') ? parse_url($env('DATABASE_URL')) : [];

return [
    'app' => [
        'nombre'       => 'Vortex',
        'eslogan'      => 'Sistema de ventas',
        'moneda'       => 'COP',
        'iva'          => 0.19,             // 19 % IVA Colombia
        'zona_horaria' => 'America/Bogota',
        'debug'        => filter_var($env('APP_DEBUG', 'true'), FILTER_VALIDATE_BOOLEAN), // false en producción
    ],

    'db' => [
        'host'    => $url['host'] ?? $env('DB_HOST', 'localhost'),
        'puerto'  => (int) ($url['port'] ?? $env('DB_PORT', 3306)),
        'nombre'  => isset($url['path']) ? ltrim($url['path'], '/') : $env('DB_NAME', 'vortex'),
        'usuario' => isset($url['user']) ? urldecode($url['user']) : $env('DB_USER', 'root'),
        'clave'   => isset($url['pass']) ? urldecode($url['pass']) : $env('DB_PASS', ''),
        'charset' => 'utf8mb4',
    ],

    // API externa: tasa de cambio COP -> USD (gratuita, sin API key)
    'api' => [
        'tasa_url'      => 'https://open.er-api.com/v6/latest/COP',
        'cache_minutos' => 360,
        'tasa_respaldo' => 0.00025,         // Se usa si no hay internet ni caché
        'timeout'       => 5,
    ],
];
