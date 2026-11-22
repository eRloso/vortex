<?php

namespace App\Models;

use App\Core\Model;

class DetalleVenta extends Model
{
    protected string $tabla = 'detalle_ventas';
    protected array $campos = ['venta_id', 'producto_id', 'cantidad', 'precio_unitario', 'importe'];

    public function deVenta(int $ventaId): array
    {
        return $this->consultar(
            'SELECT d.*, p.nombre, p.codigo
             FROM detalle_ventas d
             JOIN productos p ON p.id = d.producto_id
             WHERE d.venta_id = ?
             ORDER BY d.id',
            [$ventaId]
        );
    }
}
