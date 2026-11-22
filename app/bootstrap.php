<?php
/**
 * Arranque de la aplicación: autoload PSR-4 simple, helpers, configuración y sesión.
 */

use App\Core\Config;
use App\Core\Session;

require BASE_PATH . '/app/autoload.php';

require BASE_PATH . '/app/helpers.php';

Config::cargar(BASE_PATH . '/config/config.php');
date_default_timezone_set(Config::get('app.zona_horaria', 'America/Bogota'));

if (Config::get('app.debug')) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

Session::iniciar();
