<?php

namespace App\Core;

/**
 * Enrutador sencillo basado en el parámetro ?r=ruta y el método HTTP.
 */
final class Router
{
    private array $rutas = ['GET' => [], 'POST' => []];

    public function get(string $ruta, array $accion): self
    {
        return $this->agregar('GET', $ruta, $accion);
    }

    public function post(string $ruta, array $accion): self
    {
        return $this->agregar('POST', $ruta, $accion);
    }

    private function agregar(string $metodo, string $ruta, array $accion): self
    {
        $this->rutas[$metodo][trim($ruta, '/')] = $accion;
        return $this;
    }

    public function despachar(string $ruta, string $metodo): void
    {
        $accion = $this->rutas[strtoupper($metodo)][trim($ruta, '/')] ?? null;

        if ($accion === null) {
            http_response_code(404);
            echo (new View())->render('errores/404', [], null);
            return;
        }

        [$controlador, $funcion] = $accion;
        (new $controlador())->$funcion();
    }
}
