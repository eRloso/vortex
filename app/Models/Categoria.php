<?php

namespace App\Models;

use App\Core\Model;

class Categoria extends Model
{
    protected string $tabla = 'categorias';
    protected array $campos = ['nombre', 'color'];

    /** Categorías con la cantidad de productos activos de cada una. */
    public function listar(string $termino = ''): array
    {
        $sql = 'SELECT c.*, COUNT(p.id) AS total_productos
                FROM categorias c
                LEFT JOIN productos p ON p.categoria_id = c.id AND p.activo = 1';
        $parametros = [];

        if ($termino !== '') {
            $sql .= ' WHERE c.nombre LIKE ?';
            $parametros[] = "%{$termino}%";
        }

        return $this->consultar($sql . ' GROUP BY c.id ORDER BY c.nombre', $parametros);
    }
}
