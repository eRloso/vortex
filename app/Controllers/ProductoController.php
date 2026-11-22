<?php

namespace App\Controllers;

use App\Core\CrudController;
use App\Core\Model;
use App\Core\Validador;
use App\Models\Categoria;
use App\Models\Producto;

class ProductoController extends CrudController
{
    protected string $carpeta  = 'productos';
    protected string $singular = 'Producto';
    protected string $plural   = 'Productos';

    protected function crearModelo(): Model
    {
        return new Producto();
    }

    protected function normalizar(array $datos): array
    {
        $datos['codigo']       = mb_strtoupper($datos['codigo'] ?? '');
        $datos['categoria_id'] = ($datos['categoria_id'] ?? '') !== '' ? (int) $datos['categoria_id'] : null;
        $datos['descripcion']  = ($datos['descripcion'] ?? '') !== '' ? $datos['descripcion'] : null;
        $datos['stock_minimo'] = ($datos['stock_minimo'] ?? '') !== '' ? $datos['stock_minimo'] : 5;
        return $datos;
    }

    protected function validar(array $datos, int $id): Validador
    {
        return (new Validador($datos))
            ->requerido('codigo', 'El código')
            ->maximo('codigo', 'El código', 30)
            ->regla('codigo', !$this->modelo()->existe('codigo', $datos['codigo'], $id), 'Ya existe un producto con este código.')
            ->requerido('nombre', 'El nombre')
            ->maximo('nombre', 'El nombre', 120)
            ->numerico('precio', 'El precio', 1)
            ->numerico('stock', 'El stock', 0)
            ->numerico('stock_minimo', 'El stock mínimo', 0);
    }

    protected function datosFormulario(): array
    {
        return ['categorias' => (new Categoria())->todos('nombre ASC')];
    }
}
