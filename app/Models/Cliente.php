<?php

namespace App\Models;

use App\Core\Model;
use RuntimeException;

class Cliente extends Model
{
    /** Cliente genérico usado cuando la venta no se asocia a nadie. */
    public const CONSUMIDOR_FINAL = 1;

    protected string $tabla = 'clientes';
    protected array $campos = ['documento', 'nombre', 'email', 'telefono', 'direccion'];

    public function listar(string $termino = ''): array
    {
        $sql = 'SELECT c.*, COUNT(v.id) AS total_compras, COALESCE(SUM(v.total), 0) AS total_gastado
                FROM clientes c
                LEFT JOIN ventas v ON v.cliente_id = c.id';
        $parametros = [];

        if ($termino !== '') {
            $sql .= ' WHERE c.nombre LIKE :t1 OR c.documento LIKE :t2 OR c.email LIKE :t3';
            $parametros = ['t1' => "%{$termino}%", 't2' => "%{$termino}%", 't3' => "%{$termino}%"];
        }

        return $this->consultar($sql . ' GROUP BY c.id ORDER BY c.nombre', $parametros);
    }

    public function eliminar(int $id): bool
    {
        if ($id === self::CONSUMIDOR_FINAL) {
            throw new RuntimeException('El cliente "Consumidor final" no se puede eliminar.');
        }
        if ((int) $this->valor('SELECT COUNT(*) FROM ventas WHERE cliente_id = ?', [$id]) > 0) {
            throw new RuntimeException('No se puede eliminar un cliente que tiene ventas registradas.');
        }
        return parent::eliminar($id);
    }
}
