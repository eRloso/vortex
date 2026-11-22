<?php

namespace App\Core;

/**
 * Acceso centralizado a la configuración con notación de puntos.
 */
final class Config
{
    private static array $valores = [];

    public static function cargar(string $archivo): void
    {
        self::$valores = require $archivo;
    }

    public static function get(string $clave, $defecto = null)
    {
        $valor = self::$valores;
        foreach (explode('.', $clave) as $parte) {
            if (!is_array($valor) || !array_key_exists($parte, $valor)) {
                return $defecto;
            }
            $valor = $valor[$parte];
        }
        return $valor;
    }
}
