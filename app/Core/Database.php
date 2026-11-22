<?php

namespace App\Core;

use PDO;

/**
 * Conexión única a MariaDB (patrón Singleton).
 * Todos los modelos comparten la misma conexión, lo que permite transacciones entre ellos.
 */
final class Database
{
    private static ?PDO $conexion = null;

    private function __construct()
    {
    }

    public static function conexion(): PDO
    {
        if (self::$conexion === null) {
            $c = Config::get('db');
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $c['host'],
                $c['puerto'],
                $c['nombre'],
                $c['charset']
            );

            self::$conexion = new PDO($dsn, $c['usuario'], $c['clave'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);

            // Misma zona horaria que PHP para que "hoy" coincida en reportes
            self::$conexion->exec("SET time_zone = '" . date('P') . "'");
        }

        return self::$conexion;
    }
}
