<?php

namespace App\Core;

use RuntimeException;

/**
 * Controlador CRUD reutilizable (patrón Template Method).
 * Productos, Categorías y Clientes heredan de aquí y solo definen
 * su modelo, sus reglas de validación y cómo normalizar los datos.
 */
abstract class CrudController extends Controller
{
    /** Carpeta de vistas y prefijo de ruta, p. ej. 'productos'. */
    protected string $carpeta;
    protected string $singular;
    protected string $plural;
    protected bool $femenino = false;

    private ?Model $modelo = null;

    abstract protected function crearModelo(): Model;

    abstract protected function validar(array $datos, int $id): Validador;

    public function __construct()
    {
        parent::__construct();
        $this->requiereAutenticacion();
    }

    /* ---------- Acciones públicas ---------- */

    public function index(): void
    {
        $termino = $this->input('q');
        $this->render("{$this->carpeta}/index", [
            'titulo'    => $this->plural,
            'subtitulo' => "Administra el listado de {$this->plural}",
            'registros' => $this->modelo()->listar($termino),
            'q'         => $termino,
        ]);
    }

    public function nuevo(): void
    {
        $this->formulario([]);
    }

    public function editar(): void
    {
        $registro = $this->modelo()->buscar((int) $this->input('id'));
        if ($registro === null) {
            Session::flash('error', "{$this->singular} no encontrad{$this->sufijo()}.");
            $this->redirigir($this->carpeta);
        }
        $this->formulario($registro);
    }

    public function guardar(): void
    {
        $this->validarCsrf();

        $id    = (int) $this->input('id');
        $datos = $this->normalizar(array_map(fn ($v) => is_string($v) ? trim($v) : $v, $_POST));
        $validador = $this->validar($datos, $id);

        if (!$validador->esValido()) {
            $this->formulario(array_merge($datos, ['id' => $id ?: null]), $validador->errores());
            return;
        }

        if ($id > 0) {
            $this->modelo()->actualizar($id, $datos);
            Session::flash('exito', "{$this->singular} actualizad{$this->sufijo()} correctamente.");
        } else {
            $this->modelo()->crear($datos);
            Session::flash('exito', "{$this->singular} cread{$this->sufijo()} correctamente.");
        }

        $this->redirigir($this->carpeta);
    }

    public function eliminar(): void
    {
        $this->validarCsrf();

        try {
            $this->modelo()->eliminar((int) $this->input('id'));
            Session::flash('exito', "{$this->singular} eliminad{$this->sufijo()}.");
        } catch (RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }

        $this->redirigir($this->carpeta);
    }

    /* ---------- Ganchos que los hijos pueden sobrescribir ---------- */

    /** Ajusta tipos y valores antes de validar/guardar. */
    protected function normalizar(array $datos): array
    {
        return $datos;
    }

    /** Datos extra que necesita el formulario (p. ej. lista de categorías). */
    protected function datosFormulario(): array
    {
        return [];
    }

    /* ---------- Internos ---------- */

    protected function modelo(): Model
    {
        return $this->modelo ??= $this->crearModelo();
    }

    protected function formulario(array $registro, array $errores = []): void
    {
        $editando = !empty($registro['id']);
        $this->render("{$this->carpeta}/form", array_merge([
            'titulo'    => ($editando ? 'Editar ' : ($this->femenino ? 'Nueva ' : 'Nuevo ')) . mb_strtolower($this->singular),
            'subtitulo' => $editando ? 'Modifica la información y guarda los cambios' : 'Completa los datos del formulario',
            'registro'  => $registro,
            'errores'   => $errores,
            'editando'  => $editando,
        ], $this->datosFormulario()));
    }

    private function sufijo(): string
    {
        return $this->femenino ? 'a' : 'o';
    }
}
