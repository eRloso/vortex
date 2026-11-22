<?php

namespace App\Core;

/**
 * Controlador base: renderizado, respuestas JSON, redirecciones, entrada y seguridad.
 */
abstract class Controller
{
    protected View $vista;

    public function __construct()
    {
        $this->vista = new View();
    }

    protected function render(string $plantilla, array $datos = [], ?string $layout = 'layouts/main'): void
    {
        $datos += [
            'usuario' => Session::usuario(),
            'flash'   => Session::obtenerFlash(),
            'csrf'    => Session::csrf(),
            'scripts' => [],
        ];
        echo $this->vista->render($plantilla, $datos, $layout);
    }

    protected function json($datos, int $codigo = 200): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    protected function redirigir(string $ruta, array $parametros = []): void
    {
        header('Location: ' . url($ruta, $parametros));
        exit;
    }

    protected function requiereAutenticacion(): void
    {
        if (Session::usuario() === null) {
            Session::flash('info', 'Inicia sesión para continuar.');
            $this->redirigir('login');
        }
    }

    /** Lee un valor de POST o GET ya recortado. */
    protected function input(string $clave, string $defecto = ''): string
    {
        $valor = $_POST[$clave] ?? $_GET[$clave] ?? $defecto;
        return is_string($valor) ? trim($valor) : $defecto;
    }

    /** Cuerpo de una petición JSON (fetch). */
    protected function cuerpoJson(): array
    {
        $datos = json_decode((string) file_get_contents('php://input'), true);
        return is_array($datos) ? $datos : [];
    }

    protected function csrfValido(): bool
    {
        return Session::csrfValido($_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
    }

    protected function validarCsrf(): void
    {
        if (!$this->csrfValido()) {
            http_response_code(419);
            exit('La sesión expiró o el formulario no es válido. Vuelve atrás y recarga la página.');
        }
    }
}
