<?php

namespace App\Controllers;

use App\Core\CrudController;
use App\Core\Model;
use App\Core\Validador;
use App\Models\Categoria;

class CategoriaController extends CrudController
{
    protected string $carpeta  = 'categorias';
    protected string $singular = 'Categoría';
    protected string $plural   = 'Categorías';
    protected bool $femenino   = true;

    protected function crearModelo(): Model
    {
        return new Categoria();
    }

    protected function normalizar(array $datos): array
    {
        $datos['color'] = mb_strtoupper($datos['color'] ?? '#7C5CFF');
        return $datos;
    }

    protected function validar(array $datos, int $id): Validador
    {
        return (new Validador($datos))
            ->requerido('nombre', 'El nombre')
            ->maximo('nombre', 'El nombre', 80)
            ->regla('nombre', !$this->modelo()->existe('nombre', $datos['nombre'] ?? '', $id), 'Ya existe una categoría con este nombre.')
            ->color('color', 'El color');
    }
}
