<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Usuario;

class AuthController extends Controller
{
    public function formulario(): void
    {
        if (Session::usuario() !== null) {
            $this->redirigir('dashboard');
        }
        $this->render('auth/login', ['titulo' => 'Iniciar sesión', 'email' => ''], null);
    }

    public function ingresar(): void
    {
        $this->validarCsrf();

        $email   = $this->input('email');
        $usuario = (new Usuario())->autenticar($email, $_POST['password'] ?? '');

        if ($usuario === null) {
            $this->render('auth/login', [
                'titulo' => 'Iniciar sesión',
                'email'  => $email,
                'error'  => 'Correo o contraseña incorrectos.',
            ], null);
            return;
        }

        Session::iniciarSesionUsuario($usuario);
        Session::flash('exito', '¡Bienvenido de nuevo, ' . explode(' ', $usuario['nombre'])[0] . '!');
        $this->redirigir('dashboard');
    }

    public function salir(): void
    {
        Session::destruir();
        Session::iniciar();
        Session::flash('info', 'Cerraste sesión correctamente.');
        $this->redirigir('login');
    }
}
