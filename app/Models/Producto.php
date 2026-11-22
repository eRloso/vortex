<?php

namespace App\Models;

use App\Core\Model;

class Producto extends Model
{
    protected string $tabla = 'productos';
    protected array $campos = ['codigo', 'nombre', 'descripcion', 'categoria_id', 'precio', 'stock', 'stock_minimo', 'activo'];

    private const SELECT_BASE = "SELECT p.*, c.nombre AS categoria, c.color AS categoria_color
                                 FROM productos p
                                 LEFT JOIN categorias c ON c.id = p.categoria_id";

    public function listar(string $termino = ''): array
    {
        $sql = self::SELECT_BASE . ' WHERE p.activo = 1';
        $parametros = [];

        if ($termino !== '') {
            $sql .= ' AND (p.nombre LIKE :t1 OR p.codigo LIKE :t2 OR c.nombre LIKE :t3)';
            $parametros = ['t1' => "%{$termino}%", 't2' => "%{$termino}%", 't3' => "%{$termino}%"];
        }

        return $this->consultar($sql . ' ORDER BY p.nombre', $parametros);
    }

    /** Productos cuyo stock llegó al mínimo configurado. */
    public function stockBajo(int $limite = 6): array
    {
        return $this->consultar(
            self::SELECT_BASE . ' WHERE p.activo = 1 AND p.stock <= p.stock_minimo ORDER BY p.stock ASC LIMIT ' . (int) $limite
        );
    }

    public function contarActivos(): int
    {
        return $this->contar('activo = 1');
    }

    /**
     * Borrado lógico: el producto se oculta pero se conserva
     * para no romper el historial de ventas.
     */
    public function eliminar(int $id): bool
    {
        return $this->ejecutar('UPDATE productos SET activo = 0 WHERE id = ?', [$id]);
    }
}
