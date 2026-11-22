<?php
/**
 * Funciones de ayuda para las vistas (formato, escape y URLs).
 */

use App\Core\Config;

/** Escapa texto para imprimirlo de forma segura en HTML. */
function e($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

/** Genera la URL de una ruta del sistema. */
function url(string $ruta = 'dashboard', array $parametros = []): string
{
    return 'index.php?' . http_build_query(array_merge(['r' => $ruta], $parametros));
}

/** Ruta pública de un recurso (css, js, img). Las URL absolutas se devuelven tal cual. */
function asset(string $ruta): string
{
    return preg_match('#^https?://#', $ruta) ? $ruta : 'assets/' . ltrim($ruta, '/');
}

/** Formatea un valor como moneda. */
function dinero($valor, ?string $moneda = null): string
{
    $moneda = $moneda ?? Config::get('app.moneda', 'COP');
    $valor  = (float) $valor;

    return $moneda === 'USD'
        ? 'US$ ' . number_format($valor, 2, '.', ',')
        : '$ ' . number_format($valor, 0, ',', '.');
}

/** Formatea una fecha de la base de datos. */
function fecha(?string $valor, bool $conHora = true): string
{
    if (!$valor) {
        return '—';
    }
    return date($conHora ? 'd/m/Y h:i a' : 'd/m/Y', strtotime($valor));
}

/** Devuelve 'activo' si la ruta actual empieza por el prefijo (para el menú). */
function ruta_activa(string $prefijo): string
{
    $actual = $_GET['r'] ?? 'dashboard';
    return ($actual === $prefijo || str_starts_with($actual, $prefijo . '/')) ? 'activo' : '';
}

/** Iniciales de un nombre para los avatares. */
function iniciales(string $nombre): string
{
    $partes = preg_split('/\s+/', trim($nombre));
    $letras = array_map(fn ($p) => mb_substr($p, 0, 1), array_slice($partes, 0, 2));
    return mb_strtoupper(implode('', $letras));
}
