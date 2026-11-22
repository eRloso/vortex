<?php

namespace App\Core;

use RuntimeException;

/**
 * Motor de vistas mínimo: renderiza una plantilla dentro de un layout.
 */
final class View
{
    private string $directorio;

    public function __construct(?string $directorio = null)
    {
        $this->directorio = $directorio ?? BASE_PATH . '/app/Views';
    }

    public function render(string $plantilla, array $datos = [], ?string $layout = 'layouts/main'): string
    {
        $contenido = $this->incluir($plantilla, $datos);

        return $layout === null
            ? $contenido
            : $this->incluir($layout, array_merge($datos, ['contenido' => $contenido]));
    }

    /** Fragmentos reutilizables desde dentro de las vistas: $this->parcial('partials/campo', [...]) */
    public function parcial(string $plantilla, array $datos = []): string
    {
        return $this->incluir($plantilla, $datos);
    }

    private function incluir(string $plantilla, array $datos): string
    {
        $archivo = "{$this->directorio}/{$plantilla}.php";
        if (!is_file($archivo)) {
            throw new RuntimeException("Vista no encontrada: {$plantilla}");
        }

        extract($datos, EXTR_SKIP);
        ob_start();
        require $archivo;
        return (string) ob_get_clean();
    }
}
