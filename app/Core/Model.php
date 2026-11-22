<?php

namespace App\Core;

use InvalidArgumentException;
use PDO;

/**
 * Modelo base: CRUD genérico con consultas preparadas.
 * Cada modelo hijo solo define su tabla y sus campos permitidos ($campos).
 */
abstract class Model
{
    protected string $tabla;
    protected string $clavePrimaria = 'id';
    /** Lista blanca de columnas que se pueden insertar/actualizar. */
    protected array $campos = [];
    protected PDO $db;

    /**
     * Inyección de dependencias: en producción se usa la conexión MariaDB;
     * en las pruebas unitarias se inyecta un PDO SQLite en memoria.
     */
    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::conexion();
    }

    public function todos(string $orden = 'id DESC'): array
    {
        return $this->consultar("SELECT * FROM {$this->tabla} ORDER BY {$orden}");
    }

    /** Listado con búsqueda; los hijos lo sobrescriben para agregar filtros y JOINs. */
    public function listar(string $termino = ''): array
    {
        return $this->todos();
    }

    public function buscar(int $id): ?array
    {
        return $this->consultarUno(
            "SELECT * FROM {$this->tabla} WHERE {$this->clavePrimaria} = ?",
            [$id]
        );
    }

    public function crear(array $datos): int
    {
        $datos    = $this->filtrar($datos);
        $columnas = implode(', ', array_keys($datos));
        $marcas   = implode(', ', array_fill(0, count($datos), '?'));

        $this->ejecutar("INSERT INTO {$this->tabla} ({$columnas}) VALUES ({$marcas})", array_values($datos));
        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $datos): bool
    {
        $datos = $this->filtrar($datos);
        $set   = implode(', ', array_map(fn ($c) => "{$c} = ?", array_keys($datos)));

        return $this->ejecutar(
            "UPDATE {$this->tabla} SET {$set} WHERE {$this->clavePrimaria} = ?",
            [...array_values($datos), $id]
        );
    }

    public function eliminar(int $id): bool
    {
        return $this->ejecutar("DELETE FROM {$this->tabla} WHERE {$this->clavePrimaria} = ?", [$id]);
    }

    public function contar(string $condicion = '1', array $parametros = []): int
    {
        return (int) $this->valor("SELECT COUNT(*) FROM {$this->tabla} WHERE {$condicion}", $parametros);
    }

    /** ¿Existe otro registro con este valor? Útil para validar campos únicos. */
    public function existe(string $campo, $valor, int $exceptoId = 0): bool
    {
        if (!in_array($campo, $this->campos, true)) {
            throw new InvalidArgumentException("Campo no permitido: {$campo}");
        }
        return $this->contar("{$campo} = ? AND {$this->clavePrimaria} <> ?", [$valor, $exceptoId]) > 0;
    }

    /* ---------- Utilidades protegidas para los modelos hijos ---------- */

    protected function consultar(string $sql, array $parametros = []): array
    {
        $sentencia = $this->db->prepare($sql);
        $sentencia->execute($parametros);
        return $sentencia->fetchAll();
    }

    protected function consultarUno(string $sql, array $parametros = []): ?array
    {
        $sentencia = $this->db->prepare($sql);
        $sentencia->execute($parametros);
        return $sentencia->fetch() ?: null;
    }

    protected function valor(string $sql, array $parametros = [])
    {
        $sentencia = $this->db->prepare($sql);
        $sentencia->execute($parametros);
        return $sentencia->fetchColumn();
    }

    protected function ejecutar(string $sql, array $parametros = []): bool
    {
        return $this->db->prepare($sql)->execute($parametros);
    }

    protected function filtrar(array $datos): array
    {
        return array_intersect_key($datos, array_flip($this->campos));
    }
}
