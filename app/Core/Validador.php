<?php

namespace App\Core;

/**
 * Validador encadenable: (new Validador($datos))->requerido(...)->email(...)->esValido()
 * Solo guarda el primer error de cada campo.
 */
final class Validador
{
    private array $errores = [];

    public function __construct(private array $datos)
    {
    }

    public function requerido(string $campo, string $etiqueta): self
    {
        return $this->regla($campo, trim((string) ($this->datos[$campo] ?? '')) !== '', "{$etiqueta} es obligatorio.");
    }

    public function maximo(string $campo, string $etiqueta, int $longitud): self
    {
        return $this->regla($campo, mb_strlen((string) ($this->datos[$campo] ?? '')) <= $longitud, "{$etiqueta} admite máximo {$longitud} caracteres.");
    }

    public function numerico(string $campo, string $etiqueta, float $minimo = 0): self
    {
        $valor = $this->datos[$campo] ?? null;
        $valido = is_numeric($valor) && (float) $valor >= $minimo;
        return $this->regla($campo, $valido, "{$etiqueta} debe ser un número mayor o igual a {$minimo}.");
    }

    public function email(string $campo, string $etiqueta): self
    {
        $valor = trim((string) ($this->datos[$campo] ?? ''));
        return $this->regla($campo, $valor === '' || filter_var($valor, FILTER_VALIDATE_EMAIL) !== false, "{$etiqueta} no tiene un formato válido.");
    }

    public function color(string $campo, string $etiqueta): self
    {
        return $this->regla($campo, (bool) preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($this->datos[$campo] ?? '')), "{$etiqueta} debe ser un color hexadecimal.");
    }

    /** Regla personalizada: si $cumple es falso se registra el mensaje. */
    public function regla(string $campo, bool $cumple, string $mensaje): self
    {
        if (!$cumple && !isset($this->errores[$campo])) {
            $this->errores[$campo] = $mensaje;
        }
        return $this;
    }

    public function esValido(): bool
    {
        return $this->errores === [];
    }

    public function errores(): array
    {
        return $this->errores;
    }
}
