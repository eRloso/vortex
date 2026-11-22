<?php

namespace App\Controllers;

use App\Core\CrudController;
use App\Core\Model;
use App\Core\Validador;
use App\Models\Cliente;

class ClienteController extends CrudController
{
    protected string $carpeta  = 'clientes';
    protected string $singular = 'Cliente';
    protected string $plural   = 'Clientes';

    protected function crearModelo(): Model
    {
        return new Cliente();
    }

    protected function normalizar(array $datos): array
    {
        foreach (['email', 'telefono', 'direccion'] as $opcional) {
            $datos[$opcional] = ($datos[$opcional] ?? '') !== '' ? $datos[$opcional] : null;
        }
        return $datos;
    }

    protected function validar(array $datos, int $id): Validador
    {
        return (new Validador($datos))
            ->requerido('documento', 'El documento')
            ->maximo('documento', 'El documento', 20)
            ->regla('documento', !$this->modelo()->existe('documento', $datos['documento'] ?? '', $id), 'Ya existe un cliente con este documento.')
            ->requerido('nombre', 'El nombre')
            ->maximo('nombre', 'El nombre', 120)
            ->email('email', 'El correo');
    }
}
