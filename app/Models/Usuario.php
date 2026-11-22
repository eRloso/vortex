<?php

namespace App\Models;

use App\Core\Model;
use InvalidArgumentException;
use RuntimeException;

class Usuario extends Model
{
    public const ROLES = ['admin', 'vendedor'];
    public const LONGITUD_MINIMA_NOMBRE = 3;
    public const LONGITUD_MINIMA_CLAVE = 6;

    protected string $tabla = 'usuarios';
    protected array $campos = ['nombre', 'email', 'password', 'rol'];

    public function buscarPorEmail(string $email): ?array
    {
        return $this->consultarUno('SELECT * FROM usuarios WHERE email = ?', [self::normalizarEmail($email)]);
    }

    /** Devuelve el usuario si las credenciales son correctas, o null si no. */
    public function autenticar(string $email, string $clave): ?array
    {
        $usuario = $this->buscarPorEmail($email);
        return ($usuario && password_verify($clave, $usuario['password'])) ? $usuario : null;
    }

    /**
     * Registra un usuario aplicando las reglas de negocio.
     *
     * @throws InvalidArgumentException si un dato no cumple las reglas
     * @throws RuntimeException si el correo ya está registrado
     */
    public function registrar(string $nombre, string $email, string $clave, string $rol = 'vendedor'): int
    {
        $nombre = trim($nombre);
        $email  = self::normalizarEmail($email);

        if (mb_strlen($nombre) < self::LONGITUD_MINIMA_NOMBRE) {
            throw new InvalidArgumentException('El nombre debe tener al menos ' . self::LONGITUD_MINIMA_NOMBRE . ' caracteres.');
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('El correo electrónico no es válido.');
        }
        if (mb_strlen($clave) < self::LONGITUD_MINIMA_CLAVE) {
            throw new InvalidArgumentException('La contraseña debe tener al menos ' . self::LONGITUD_MINIMA_CLAVE . ' caracteres.');
        }
        if (!in_array($rol, self::ROLES, true)) {
            throw new InvalidArgumentException('Rol no válido.');
        }
        if ($this->buscarPorEmail($email) !== null) {
            throw new RuntimeException('Ya existe un usuario registrado con ese correo.');
        }

        return $this->crear(['nombre' => $nombre, 'email' => $email, 'password' => $clave, 'rol' => $rol]);
    }

    /** Guarda siempre la contraseña cifrada (bcrypt), nunca en texto plano. */
    public function crear(array $datos): int
    {
        $datos['password'] = password_hash($datos['password'], PASSWORD_BCRYPT);
        return parent::crear($datos);
    }

    private static function normalizarEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
