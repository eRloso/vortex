<?php

namespace App\Core;

/**
 * Envoltorio de la sesión: usuario autenticado, mensajes flash y token CSRF.
 */
final class Session
{
    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
            ]);
        }
    }

    public static function get(string $clave, $defecto = null)
    {
        return $_SESSION[$clave] ?? $defecto;
    }

    public static function set(string $clave, $valor): void
    {
        $_SESSION[$clave] = $valor;
    }

    public static function destruir(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public static function usuario(): ?array
    {
        return self::get('usuario');
    }

    public static function iniciarSesionUsuario(array $usuario): void
    {
        session_regenerate_id(true);
        self::set('usuario', [
            'id'     => (int) $usuario['id'],
            'nombre' => $usuario['nombre'],
            'email'  => $usuario['email'],
            'rol'    => $usuario['rol'],
        ]);
    }

    /* ---------- Mensajes flash (se muestran una sola vez) ---------- */

    public static function flash(string $tipo, string $mensaje): void
    {
        $_SESSION['_flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
    }

    public static function obtenerFlash(): array
    {
        $mensajes = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $mensajes;
    }

    /* ---------- Protección CSRF ---------- */

    public static function csrf(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function csrfValido(?string $token): bool
    {
        return is_string($token) && hash_equals(self::csrf(), $token);
    }
}
